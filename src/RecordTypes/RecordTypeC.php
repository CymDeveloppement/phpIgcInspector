<?php

namespace Ycdev\PhpIgcInspector\RecordTypes;

use Ycdev\PhpIgcInspector\Exception\InvalidIgcException;

/**
 * Record Type C - Task/declaration
 * 
 * Format IGC pour les tâches :
 * - Première ligne : C + date (DDMMYY) + heure (HHMMSS) + autres données
 * - Lignes suivantes : C + latitude (DDMMmmmN/S) + longitude (DDDMMmmmE/W) + nom optionnel
 * 
 * Les waypoints sont stockés dans l'ordre : départ, tour 1, tour 2, ..., arrivée
 */
class RecordTypeC extends AbstractRecordType
{
    protected string $recordId = 'Task';
    
    /**
     * Format pour la première ligne (déclaration de tâche)
     */
    protected array $formatDeclaration = [
        ['declarationDate', '/^C(\d{6})/', '/^\d{6}$/'],
        ['declarationTime', '/^\d{6}(\d{6})/', '/^\d{6}$/'],
        ['declarationData']
    ];
    
    /**
     * Format pour les lignes de waypoints
     */
    protected array $formatWaypoint = [
        ['latitude', '/^C(\d{7})/', '/^\d{7}$/'],
        ['latitudeNS', '/^\d{7}([NS])/', '/^[NS]$/'],
        ['longitude', '/^[NS](\d{8})/', '/^\d{8}$/'],
        ['longitudeEW', '/^\d{8}([EW])/', '/^[EW]$/'],
        ['name']
    ];

    public function matches(string $line): bool
    {
        return strlen($line) > 0 && $line[0] === 'C';
    }

    public function parse(): object|null
    {
        $this->check();
        
        // Vérifier si c'est la première ligne de déclaration ou un waypoint
        // Priorité : vérifier d'abord le format waypoint (plus spécifique)
        // Un waypoint commence par C suivi de coordonnées (C + 7 chiffres + N/S + 8 chiffres + E/W)
        // Format: C5111643N00102000W... ou C0000000N00000000E
        if (preg_match('/^C\d{7}[NS]\d{8}[EW]/', $this->line)) {
            // C'est un waypoint
            return $this->parseWaypoint();
        } elseif (preg_match('/^C\d{6}\d{6}/', $this->line)) {
            // C'est une ligne de déclaration (C + date DDMMYY + heure HHMMSS)
            // Format: C050822090459...
            return $this->parseDeclaration();
        } else {
            // Format non reconnu, essayer comme waypoint par défaut
            return $this->parseWaypoint();
        }
    }
    
    /**
     * Parse la ligne de déclaration de tâche
     * Format : C + date (DDMMYY) + heure (HHMMSS) + autres données
     * Exemple : C050822090459000000000502
     */
    private function parseDeclaration(): object
    {
        $data = [];
        
        // Extraire la date (DDMMYY) - positions 1-6 après le C
        if (preg_match('/^C(\d{6})(\d{6})/', $this->line, $matches)) {
            $dateStr = $matches[1];
            $timeStr = $matches[2];
            
            // Convertir DDMMYY en YYYY-MM-DD
            $day = substr($dateStr, 0, 2);
            $month = substr($dateStr, 2, 2);
            $year = '20' . substr($dateStr, 4, 2);
            $data['declarationDate'] = $year . '-' . $month . '-' . $day;
            $data['declarationDateRaw'] = $dateStr;
            
            // Extraire l'heure (HHMMSS)
            $hour = substr($timeStr, 0, 2);
            $minute = substr($timeStr, 2, 2);
            $second = substr($timeStr, 4, 2);
            $data['declarationTime'] = $hour . ':' . $minute . ':' . $second;
            $data['declarationTimeRaw'] = $timeStr;
            
            // Extraire le reste des données (après C + 12 chiffres)
            if (strlen($this->line) > 13) {
                $data['declarationData'] = trim(substr($this->line, 13));
            } else {
                $data['declarationData'] = null;
            }
        }
        
        if ($this->withRaw) {
            $data['raw'] = $this->line;
        }
        
        return (object) $data;
    }
    
    /**
     * Parse une ligne de waypoint
     * Format : C + latitude (DDMMmmmN/S) + longitude (DDDMMmmmE/W) + nom optionnel
     */
    private function parseWaypoint(): object
    {
        $data = [];
        
        // Extraire la latitude (DDMMmmm)
        if (preg_match('/^C(\d{7})([NS])/', $this->line, $matches)) {
            $latStr = $matches[1];
            $latNS = $matches[2];
            
            // Convertir en degrés décimaux (même méthode que RecordTypeB)
            $latDegrees = (int) substr($latStr, 0, 2);
            $latMinutes = (int) substr($latStr, 2, 2);
            $latThousandths = (int) substr($latStr, 4, 3);
            $decimalMinutes = $latMinutes + ($latThousandths / 1000);
            $latitude = $latDegrees + ($decimalMinutes / 60);
            
            if ($latNS === 'S') {
                $latitude = -$latitude;
            }
            
            $data['latitude'] = $latitude;
            $data['latitudeRaw'] = $latStr;
            $data['latitudeNS'] = $latNS;
        }
        
        // Extraire la longitude (DDDMMmmm)
        // Format: C + latitude (7 chiffres) + N/S + longitude (8 chiffres) + E/W
        if (preg_match('/^C\d{7}([NS])(\d{8})([EW])/', $this->line, $matches)) {
            $lonStr = $matches[2];
            $lonEW = $matches[3];
            
            // Convertir en degrés décimaux
            $lonDegrees = (int) substr($lonStr, 0, 3);
            $lonMinutes = (int) substr($lonStr, 3, 2);
            $lonThousandths = (int) substr($lonStr, 5, 3);
            $decimalMinutes = $lonMinutes + ($lonThousandths / 1000);
            $longitude = $lonDegrees + ($decimalMinutes / 60);
            
            if ($lonEW === 'W') {
                $longitude = -$longitude;
            }
            
            $data['longitude'] = $longitude;
            $data['longitudeRaw'] = $lonStr;
            $data['longitudeEW'] = $lonEW;
        }
        
        // Extraire le nom du waypoint (optionnel, après les coordonnées)
        // Le nom commence après les coordonnées (C + 7 chiffres + N/S + 8 chiffres + E/W)
        if (preg_match('/^C\d{7}[NS]\d{8}[EW](.+)$/', $this->line, $matches)) {
            $data['name'] = trim($matches[1]);
        } else {
            $data['name'] = null;
        }
        
        // Détecter si c'est un point de départ/arrivée (coordonnées 0,0)
        if (isset($data['latitude']) && isset($data['longitude'])) {
            if ($data['latitude'] == 0 && $data['longitude'] == 0) {
                $data['isStartFinish'] = true;
            } else {
                $data['isStartFinish'] = false;
            }
        }
        
        if ($this->withRaw) {
            $data['raw'] = $this->line;
        }
        
        return (object) $data;
    }

    public function check(): bool
    {
        // Vérifier que la ligne commence par C
        if (strlen($this->line) < 2) {
            throw new InvalidIgcException(
                sprintf('Ligne %d : enregistrement C invalide (trop court)', $this->lineNumber)
            );
        }
        
        // Vérifier le format selon le type de ligne
        if (preg_match('/^C\d{6}/', $this->line)) {
            // Ligne de déclaration : doit avoir au moins C + 6 chiffres (date)
            if (strlen($this->line) < 7) {
                throw new InvalidIgcException(
                    sprintf('Ligne %d : déclaration de tâche invalide (format attendu: C + date)', $this->lineNumber)
                );
            }
        } else {
            // Ligne de waypoint : doit avoir au moins C + coordonnées
            if (!preg_match('/^C\d{7}[NS]\d{8}[EW]/', $this->line)) {
                throw new InvalidIgcException(
                    sprintf('Ligne %d : waypoint invalide (format attendu: C + latitude + longitude)', $this->lineNumber)
                );
            }
        }
        
        return true;
    }
}
