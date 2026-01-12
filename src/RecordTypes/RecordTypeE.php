<?php

namespace Ycdev\PhpIgcInspector\RecordTypes;

use Ycdev\PhpIgcInspector\Exception\InvalidIgcException;
use Ycdev\PhpIgcInspector\Data\EventTypeCode;

/**
 * Record Type E - Event
 * 
 * Format : E HHMMSS XXX...
 * - HHMMSS : Heure UTC de l'événement (6 chiffres)
 * - XXX... : Code ou description de l'événement (variable)
 * 
 * Types d'événements courants :
 * - PEV : Pilot Event (événement pilote)
 * - START, ST : Départ
 * - FINISH, FN : Arrivée
 * - TP : Turnpoint
 * - BFION, BFIOFF : Activation/désactivation de fonctions
 * - MOTOR : Activation moteur
 * - TAKEOFF : Décollage
 * - LANDING : Atterrissage
 */
class RecordTypeE extends AbstractRecordType
{
    protected string $recordId = 'Event';
    protected bool $ignoreRecord = false;
    
    protected array $format = [
        ['time', '/^E(\d{6})/', '/^\d{6}$/'],
        ['eventCode', '/^\d{6}([A-Z]{2,})/', '/^[A-Z]{2,}$/'],
        ['eventData']
    ];

    public function matches(string $line): bool
    {
        return strlen($line) > 0 && $line[0] === 'E';
    }

    public function parse(): object|null
    {
        $this->check();
        
        $data = $this->extract();
        
        // Convertir l'heure en format lisible
        if (isset($data['time']) && !empty($data['time'])) {
            $timeStr = $data['time'];
            $hour = substr($timeStr, 0, 2);
            $minute = substr($timeStr, 2, 2);
            $second = substr($timeStr, 4, 2);
            $data['timeFormatted'] = $hour . ':' . $minute . ':' . $second;
            
            // Le timestamp sera calculé dans finalizeEvents() avec la date du vol
        }
        
        // Analyser le code d'événement pour déterminer le type
        if (isset($data['eventCode'])) {
            $eventCode = $data['eventCode'];
            
            // Détecter le type d'événement en utilisant EventTypeCode
            $data['eventType'] = EventTypeCode::detectEventType($eventCode);
            $data['eventTypeDescription'] = EventTypeCode::getEventTypeDescription($data['eventType']);
            $data['isRecognized'] = EventTypeCode::isRecognized($eventCode);
            
            // Extraire des informations supplémentaires si disponibles
            if (isset($data['eventData']) && !empty($data['eventData'])) {
                $data['eventDescription'] = trim($data['eventData']);
            } else {
                $data['eventDescription'] = null;
            }
        }
        
        if ($this->withRaw) {
            $data['raw'] = $this->line;
        }
        
        return (object) $data;
    }
    

    public function check(): bool
    {
        // Vérifier que la ligne commence par E
        if (strlen($this->line) < 8) {
            throw new InvalidIgcException(
                sprintf('Ligne %d : enregistrement E invalide (trop court, minimum 8 caractères)', $this->lineNumber)
            );
        }
        
        // Vérifier le format : E + 6 chiffres (heure) + au moins 2 caractères (code)
        if (!preg_match('/^E\d{6}[A-Z]/', $this->line)) {
            throw new InvalidIgcException(
                sprintf('Ligne %d : enregistrement E invalide (format attendu: E + heure + code)', $this->lineNumber)
            );
        }
        
        return true;
    }
}
