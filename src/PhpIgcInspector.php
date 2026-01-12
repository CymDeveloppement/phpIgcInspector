<?php

namespace Ycdev\PhpIgcInspector;

use Ycdev\PhpIgcInspector\Exception\InvalidIgcException;
use Ycdev\PhpIgcInspector\RecordTypes\RecordTypeInterface;
use Ycdev\PhpIgcInspector\RecordTypes\AbstractRecordType;
use Ycdev\PhpIgcInspector\PhpIgcUtils;

/**
 * Classe pour lire et manipuler les fichiers IGC
 * 
 * Le format IGC (International Gliding Commission) est utilisé pour 
 * enregistrer les données de vol des planeurs.
 * 
 * @package Ycdev\PhpIgcInspector
 */
class PhpIgcInspector
{
    private string $content;
    private ?object $flight = null;
    private bool $withRaw = true;
    /**
     * Constructeur
     * 
     * @param string $content Contenu du fichier IGC
     * @param bool $withRaw Si true, ajouter le champ 'raw' dans les données parsées
     */
    public function __construct(string $content, bool $withRaw = false)
    {
        $this->content = $content;
        $this->withRaw = $withRaw;
    }
    
    /**
     * Crée une instance depuis un fichier
     * 
     * @param string $filePath Chemin vers le fichier IGC
     * @param bool $withRaw Si true, ajouter le champ 'raw' dans les données parsées
     * @return self
     * @throws \RuntimeException Si le fichier ne peut pas être lu
     */
    public static function fromFile(string $filePath, bool $withRaw = false): self
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Le fichier IGC n'existe pas : {$filePath}");
        }
        
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new \RuntimeException("Impossible de lire le fichier IGC : {$filePath}");
        }
        
        return new self($content, $withRaw);
    }

    /**
     * Valide le contenu du fichier IGC
     * 
     * @return bool True si le fichier est valide
     * @throws InvalidIgcException Si le fichier n'est pas valide
     */
    public function validate(): bool
    {
        if (empty(trim($this->content))) {
            throw new InvalidIgcException('Le fichier IGC est vide');
        }

        $lines = explode("\n", $this->content);
        
        // Supprimer les lignes vides
        $lines = array_filter($lines, function($line) {
            return !empty(trim($line));
        });
        
        // Réindexer le tableau
        $lines = array_values($lines);
        
        $validLines = 0;
        $previousRecordType = null;
        $seenRecordTypes = []; // Suivre les types d'enregistrements déjà vus
        $flight = (object) []; // Objet global du fichier

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);

            // Vérifier que chaque ligne commence par une lettre majuscule valide
            $firstChar = $line[0];
            
            // Vérifier que la classe RecordType existe pour ce préfixe
            $className = 'Ycdev\\PhpIgcInspector\\RecordTypes\\RecordType' . $firstChar;
            if (!class_exists($className)) {
                throw new InvalidIgcException(
                    sprintf('Ce type d\'enregistrement "%s" n\'est pas pris en charge (ligne %d)', $firstChar, $lineNum + 1)
                );
            }

            // Instancier la classe RecordType
            /** @var AbstractRecordType $record */
            $record = new $className($line, $lineNum + 1, $previousRecordType, $this->withRaw, $flight);
            
            // Si cet enregistrement doit être ignoré, passer à la ligne suivante
            if ($record->isIgnoreRecord()) {
                $previousRecordType = $firstChar;
                continue;
            }
            
            // Vérifier si ce type d'enregistrement est unique
            if ($record->isSingleRecord()) {
                if (isset($seenRecordTypes[$firstChar])) {
                    throw new InvalidIgcException(
                        sprintf(
                            'Ligne %d : le type d\'enregistrement "%s" ne doit apparaître qu\'une seule fois dans le fichier (déjà vu à la ligne %d)',
                            $lineNum + 1,
                            $firstChar,
                            $seenRecordTypes[$firstChar]
                        )
                    );
                }
                $seenRecordTypes[$firstChar] = $lineNum + 1;
            }
            
            // Valider l'enregistrement
            try {
                if (!$record->check()) {
                    throw new InvalidIgcException(
                        sprintf('Ligne %d : enregistrement invalide', $lineNum + 1)
                    );
                }
            } catch (InvalidIgcException $e) {
                throw $e;
            }
            
            // Parser l'enregistrement et l'ajouter au vol
            $recordId = $record->getRecordId();
            $parsedData = $record->parse();
            
            // Traitement spécial pour RecordTypeC (Task)
            if ($firstChar === 'C') {
                $this->processTaskRecord($flight, $parsedData);
            } elseif ($firstChar === 'E') {
                // Traitement spécial pour RecordTypeE (Event)
                $this->processEventRecord($flight, $parsedData);
            } elseif ($record->isSingleRecord()) {
                // Si le record est unique, stocker directement
                $flight->$recordId = $parsedData;
            } elseif ($record->isSingleObject()) {
                // Si les enregistrements multiples doivent être fusionnés dans un seul objet
                if (!isset($flight->$recordId)) {
                    $flight->$recordId = new \stdClass();
                    if ($this->withRaw) {
                        $flight->$recordId->raw = [];
                    }
                }
                // Fusionner les données parsées dans l'objet existant
                foreach ($parsedData as $key => $value) {
                    // Ignorer les champs null
                    if ($value === null) {
                        continue;
                    }
                    
                    if ($key === 'raw' && $this->withRaw) {
                        // Ajouter la ligne brute dans un tableau
                        $flight->$recordId->raw[] = $value;
                    } elseif ($key !== 'raw') {
                        $flight->$recordId->$key = $value;
                    }
                }
            } else {
                // Si le record peut apparaître plusieurs fois, créer un tableau
                if (!isset($flight->$recordId)) {
                    $flight->$recordId = [];
                }
                if(!is_null($parsedData)) {
                    $flight->$recordId[] = $parsedData;
                }
            }
            
            $previousRecordType = $firstChar;
            $validLines++;
        }

        if ($validLines === 0) {
            throw new InvalidIgcException('Le fichier IGC ne contient aucune ligne valide');
        }
        
        // Stocker l'objet flight dans une propriété
        $this->flight = $flight;
        
        // Finaliser la structure de la tâche si elle existe
        $this->finalizeTask();
        
        // Finaliser la structure des événements si elle existe
        $this->finalizeEvents();
        
        // Calculer les valeurs dérivées après le parsing complet
        $this->calculateDerivedValues();

        return true;
    }
    
    /**
     * Valide les turnpoints en vérifiant la proximité des points GPS avec les waypoints de la tâche
     * 
     * @param float $proximityRadius Distance en mètres pour valider la proximité d'un turnpoint (défaut: 5000 m)
     * @return bool True si tous les turnpoints sont validés dans l'ordre
     */
    public function validTurnPoint(float $proximityRadius = 5000.0): bool
    {
        if ($this->flight === null || !isset($this->flight->Task) || !isset($this->flight->Fix)) {
            return false;
        }
        
        $task = $this->flight->Task;
        
        // Vérifier qu'il y a des waypoints et des points GPS
        if (!isset($task->waypoints) || empty($task->waypoints) || 
            !is_array($this->flight->Fix) || empty($this->flight->Fix)) {
            return false;
        }
        
        // Initialiser les résultats de validation
        if (!isset($task->turnPointValidation)) {
            $task->turnPointValidation = (object) [
                'proximityRadius' => $proximityRadius,
                'validatedTurnPoints' => [],
                'missedTurnPoints' => [],
                'allValidated' => false,
                'validationOrder' => []
            ];
        }
        
        $validation = $task->turnPointValidation;
        $validation->proximityRadius = $proximityRadius;
        
        // Créer la liste des waypoints à valider (départ, tours, arrivée)
        $waypointsToValidate = [];
        
        if (isset($task->start)) {
            $waypointsToValidate[] = ['waypoint' => $task->start, 'type' => 'start', 'index' => 0];
        }
        
        if (isset($task->turnPoints)) {
            foreach ($task->turnPoints as $index => $turnPoint) {
                $waypointsToValidate[] = ['waypoint' => $turnPoint, 'type' => 'turn', 'index' => $index + 1];
            }
        }
        
        if (isset($task->finish)) {
            $waypointsToValidate[] = ['waypoint' => $task->finish, 'type' => 'finish', 'index' => count($waypointsToValidate)];
        }
        
        // Parcourir les points GPS dans l'ordre chronologique
        $currentWaypointIndex = 0;
        $validatedWaypoints = [];
        $missedWaypoints = [];
        $validationOrder = [];
        
        foreach ($this->flight->Fix as $fixIndex => $fix) {
            if (!isset($fix->latitude) || !isset($fix->longitude)) {
                continue;
            }
            
            // Vérifier si on a déjà validé tous les waypoints
            if ($currentWaypointIndex >= count($waypointsToValidate)) {
                break;
            }
            
            $targetWaypoint = $waypointsToValidate[$currentWaypointIndex];
            $waypoint = $targetWaypoint['waypoint'];
            
            if (!isset($waypoint->latitude) || !isset($waypoint->longitude)) {
                continue;
            }
            
            // Calculer la distance entre le point GPS et le waypoint
            $distance = PhpIgcUtils::calculateProximity(
                $fix->latitude,
                $fix->longitude,
                $waypoint->latitude,
                $waypoint->longitude
            );
            
            // Vérifier si le point est dans le rayon de proximité
            if ($distance <= $proximityRadius) {
                // Waypoint validé
                $validatedWaypoint = (object) [
                    'waypoint' => $waypoint,
                    'type' => $targetWaypoint['type'],
                    'index' => $targetWaypoint['index'],
                    'validatedAt' => isset($fix->dateTime) ? $fix->dateTime : null,
                    'validatedAtTimestamp' => isset($fix->timestamp) ? $fix->timestamp : null,
                    'distance' => round($distance, 2),
                    'fixIndex' => $fixIndex
                ];
                
                $validatedWaypoints[] = $validatedWaypoint;
                $validationOrder[] = $validatedWaypoint;
                
                // Passer au waypoint suivant
                $currentWaypointIndex++;
            }
        }
        
        // Identifier les waypoints manqués
        for ($i = $currentWaypointIndex; $i < count($waypointsToValidate); $i++) {
            $missedWaypoint = (object) [
                'waypoint' => $waypointsToValidate[$i]['waypoint'],
                'type' => $waypointsToValidate[$i]['type'],
                'index' => $waypointsToValidate[$i]['index']
            ];
            $missedWaypoints[] = $missedWaypoint;
        }
        
        // Mettre à jour les résultats
        $validation->validatedTurnPoints = $validatedWaypoints;
        $validation->missedTurnPoints = $missedWaypoints;
        $validation->validationOrder = $validationOrder;
        $validation->allValidated = empty($missedWaypoints);
        $validation->validatedCount = count($validatedWaypoints);
        $validation->totalWaypoints = count($waypointsToValidate);
        
        return $validation->allValidated;
    }
    
    
    /**
     * Traite un enregistrement de type E (Event)
     * 
     * @param object $flight Objet flight
     * @param object|null $parsedData Données parsées
     */
    private function processEventRecord(object $flight, ?object $parsedData): void
    {
        if ($parsedData === null) {
            return;
        }
        
        // Initialiser l'objet Events s'il n'existe pas
        if (!isset($flight->Events)) {
            $flight->Events = (object) [
                'events' => [],
                'eventsByType' => (object) []
            ];
        }
        
        // Ajouter l'événement à la liste
        $flight->Events->events[] = $parsedData;
        
        // Grouper par type d'événement
        $eventType = $parsedData->eventType ?? 'other';
        if (!isset($flight->Events->eventsByType->$eventType)) {
            $flight->Events->eventsByType->$eventType = [];
        }
        $flight->Events->eventsByType->$eventType[] = $parsedData;
    }
    
    /**
     * Finalise la structure des événements après le parsing complet
     */
    private function finalizeEvents(): void
    {
        if ($this->flight === null || !isset($this->flight->Events)) {
            return;
        }
        
        $events = $this->flight->Events;
        
        if (!isset($events->events) || empty($events->events)) {
            return;
        }
        
        // Récupérer la date du vol pour calculer les timestamps
        $flightDate = null;
        if (isset($this->flight->OtherInformation->date)) {
            $flightDate = $this->flight->OtherInformation->date;
        } elseif (isset($this->flight->Fix) && !empty($this->flight->Fix)) {
            // Utiliser la date du premier point GPS
            $firstFix = $this->flight->Fix[0];
            if (isset($firstFix->date)) {
                $flightDate = $firstFix->date;
            }
        }
        
        // Calculer les timestamps pour chaque événement
        foreach ($events->events as $event) {
            if (isset($event->timeFormatted) && $flightDate !== null) {
                try {
                    $event->timestamp = strtotime($flightDate . ' ' . $event->timeFormatted);
                    if ($event->timestamp !== false) {
                        $event->dateTime = date('Y-m-d H:i:s', $event->timestamp);
                    }
                } catch (\Exception $e) {
                    // Ignorer les erreurs de conversion
                }
            }
        }
        
        // Trier les événements par timestamp (ordre chronologique)
        usort($events->events, function($a, $b) {
            $tsA = $a->timestamp ?? 0;
            $tsB = $b->timestamp ?? 0;
            return $tsA <=> $tsB;
        });
        
        // Identifier les événements importants
        foreach ($events->events as $event) {
            $eventType = $event->eventType ?? 'other';
            
            switch ($eventType) {
                case 'start':
                    if (!isset($events->start)) {
                        $events->start = $event;
                    }
                    break;
                case 'finish':
                    $events->finish = $event; // Le dernier finish sera conservé
                    break;
                case 'takeoff':
                    if (!isset($events->takeoff)) {
                        $events->takeoff = $event;
                    }
                    break;
                case 'landing':
                    $events->landing = $event; // Le dernier landing sera conservé
                    break;
            }
        }
        
        // Statistiques
        $events->eventCount = count($events->events);
        $events->eventTypes = array_keys((array) $events->eventsByType);
        
        // Compter les événements par type
        $eventsByTypeArray = [];
        foreach ($events->eventsByType as $type => $typeEvents) {
            $eventsByTypeArray[$type] = [
                'count' => count($typeEvents),
                'events' => $typeEvents
            ];
        }
        $events->eventsByType = (object) $eventsByTypeArray;
    }
    
    /**
     * Traite un enregistrement de type C (Task)
     * 
     * @param object $flight Objet flight
     * @param object|null $parsedData Données parsées
     */
    private function processTaskRecord(object $flight, ?object $parsedData): void
    {
        if ($parsedData === null) {
            return;
        }
        
        // Initialiser l'objet Task s'il n'existe pas
        if (!isset($flight->Task)) {
            $flight->Task = (object) [
                'declaration' => null,
                'waypoints' => []
            ];
        }
        
        // Vérifier si c'est une déclaration (contient declarationDate)
        if (isset($parsedData->declarationDate)) {
            $flight->Task->declaration = $parsedData;
        } else {
            // C'est un waypoint
            // Ignorer les waypoints avec des coordonnées 0,0 (C0000000N00000000E)
            if (isset($parsedData->isStartFinish) && $parsedData->isStartFinish) {
                // Ignorer ce waypoint (coordonnées 0,0)
                return;
            }
            
            // Vérifier aussi explicitement les coordonnées nulles
            if (isset($parsedData->latitude) && isset($parsedData->longitude)) {
                if ($parsedData->latitude == 0 && $parsedData->longitude == 0) {
                    // Ignorer ce waypoint
                    return;
                }
            }
            
            // Ajouter le waypoint valide
            $flight->Task->waypoints[] = $parsedData;
        }
    }
    
    /**
     * Finalise la structure de la tâche après le parsing complet
     */
    private function finalizeTask(): void
    {
        if ($this->flight === null || !isset($this->flight->Task)) {
            return;
        }
        
        $task = $this->flight->Task;
        
        // Identifier le départ et l'arrivée
        if (isset($task->waypoints) && count($task->waypoints) > 0) {
            // Le premier waypoint non-startFinish est généralement le départ
            // Le dernier waypoint non-startFinish est généralement l'arrivée
            $startFinishPoints = [];
            $turnPoints = [];
            
            foreach ($task->waypoints as $waypoint) {
                if (isset($waypoint->isStartFinish) && $waypoint->isStartFinish) {
                    $startFinishPoints[] = $waypoint;
                } else {
                    $turnPoints[] = $waypoint;
                }
            }
            
            // Départ : premier waypoint non-startFinish ou premier startFinish
            if (!empty($turnPoints)) {
                $task->start = $turnPoints[0];
            } elseif (!empty($startFinishPoints)) {
                $task->start = $startFinishPoints[0];
            }
            
            // Arrivée : dernier waypoint non-startFinish ou dernier startFinish
            if (!empty($turnPoints)) {
                $task->finish = $turnPoints[count($turnPoints) - 1];
            } elseif (count($startFinishPoints) > 1) {
                $task->finish = $startFinishPoints[count($startFinishPoints) - 1];
            } elseif (count($startFinishPoints) === 1) {
                $task->finish = $startFinishPoints[0];
            }
            
            // Points de tour (tous sauf départ et arrivée)
            if (count($turnPoints) > 2) {
                $task->turnPoints = array_slice($turnPoints, 1, -1);
            } else {
                $task->turnPoints = [];
            }
            
            // Nombre total de waypoints
            $task->waypointCount = count($task->waypoints);
            $task->turnPointCount = count($task->turnPoints);
            
            // Calculer la distance totale de la tâche
            $task->taskDistance = $this->calculateTaskDistance($task);
            if ($task->taskDistance !== null) {
                $task->taskDistanceKm = round($task->taskDistance / 1000, 2);
                $task->taskDistanceFormatted = number_format($task->taskDistanceKm, 2, '.', ' ') . ' km';
            }
            
            // Valider automatiquement les turnpoints si des waypoints existent
            if ($task->waypointCount > 0 && isset($this->flight->Fix) && count($this->flight->Fix) > 0) {
                $this->validTurnPoint(5000.0);
            }
        }
    }
    
    /**
     * Calcule la distance totale de la tâche en additionnant les distances entre les waypoints
     * 
     * @param object $task Objet Task
     * @return float|null Distance totale en mètres, ou null si impossible à calculer
     */
    private function calculateTaskDistance(object $task): ?float
    {
        // Créer la liste des waypoints dans l'ordre (départ → tours → arrivée)
        $waypointsInOrder = [];
        
        if (isset($task->start)) {
            $waypointsInOrder[] = $task->start;
        }
        
        if (isset($task->turnPoints)) {
            foreach ($task->turnPoints as $turnPoint) {
                $waypointsInOrder[] = $turnPoint;
            }
        }
        
        if (isset($task->finish)) {
            $waypointsInOrder[] = $task->finish;
        }
        
        // Si moins de 2 waypoints, impossible de calculer une distance
        if (count($waypointsInOrder) < 2) {
            return null;
        }
        
        // Calculer la distance totale en additionnant les distances entre waypoints consécutifs
        $totalDistance = 0.0;
        
        for ($i = 0; $i < count($waypointsInOrder) - 1; $i++) {
            $waypoint1 = $waypointsInOrder[$i];
            $waypoint2 = $waypointsInOrder[$i + 1];
            
            if (!isset($waypoint1->latitude) || !isset($waypoint1->longitude) ||
                !isset($waypoint2->latitude) || !isset($waypoint2->longitude)) {
                continue;
            }
            
            $segmentDistance = PhpIgcUtils::calculateProximity(
                $waypoint1->latitude,
                $waypoint1->longitude,
                $waypoint2->latitude,
                $waypoint2->longitude
            );
            
            $totalDistance += $segmentDistance;
        }
        
        return $totalDistance;
    }
    
    /**
     * Calcule et ajoute des valeurs dérivées après le parsing complet
     * 
     * Cette méthode ajoute des conversions et calculs utiles :
     * - Conversion du temps total en format hh:mm:ss
     * - Conversion des distances en kilomètres
     * - Calcul de la vitesse moyenne
     * - Calcul des altitudes min/max
     * - etc.
     */
    private function calculateDerivedValues(): void
    {
        if ($this->flight === null) {
            return;
        }
        
        // Calculer les valeurs dérivées dans OtherInformation
        if (isset($this->flight->OtherInformation)) {
            $info = $this->flight->OtherInformation;
            
            // Conversion du temps total en format hh:mm:ss
            if (isset($info->totalTime) && $info->totalTime > 0) {
                $info->totalTimeFormatted = PhpIgcUtils::secondsToTime($info->totalTime);
                $info->totalTimeHours = round($info->totalTime / 3600, 2);
            }
            
            // Conversion de la distance totale en kilomètres
            if (isset($info->totalDistance) && $info->totalDistance > 0) {
                $info->totalDistanceKm = round($info->totalDistance / 1000, 2);
                $info->totalDistanceFormatted = number_format($info->totalDistanceKm, 2, '.', ' ') . ' km';
            }
            
            // Calcul de la vitesse moyenne (km/h)
            if (isset($info->totalTime) && isset($info->totalDistance) && 
                $info->totalTime > 0 && $info->totalDistance > 0) {
                $info->averageSpeed = round(($info->totalDistance / $info->totalTime) * 3.6, 2);
            }
            
            // Formatage de la vitesse max
            if (isset($info->maxSpeed)) {
                $info->maxSpeedFormatted = number_format($info->maxSpeed, 2, '.', ' ') . ' km/h';
            }
        }
        
        // Calculer les altitudes min/max depuis les points GPS
        if (isset($this->flight->Fix) && is_array($this->flight->Fix) && count($this->flight->Fix) > 0) {
            $altitudesQnh = []; // Altitudes barométriques QNH (absolues)
            $altitudesQfe = []; // Altitudes barométriques QFE (relatives au décollage)
            $gnssAltitudes = [];
            
            // Récupérer l'altitude QNH du premier point (décollage)
            $firstFix = $this->flight->Fix[0];
            $takeoffQnh = $firstFix->pressureAltitude ?? $firstFix->barometricAltitude ?? $firstFix->qnh ?? null;
            
            foreach ($this->flight->Fix as $fix) {
                // Altitude barométrique QNH (absolue) - peut être dans pressureAltitude, barometricAltitude ou qnh
                $qnhAlt = $fix->pressureAltitude ?? $fix->barometricAltitude ?? $fix->qnh ?? null;
                if ($qnhAlt !== null) {
                    $altitudesQnh[] = $qnhAlt;
                    
                    // Calculer l'altitude QFE relative au décollage
                    // QFE = altitude relative au niveau de l'aérodrome (décollage)
                    if ($takeoffQnh !== null) {
                        $qfe = $qnhAlt - $takeoffQnh;
                        $fix->qfe = $qfe; // QFE = altitude relative au décollage
                        $fix->altitudeRelative = $qfe; // Alias
                        $altitudesQfe[] = $qfe;
                    }
                }
                if (isset($fix->gnssAltitude) && $fix->gnssAltitude !== null) {
                    $gnssAltitudes[] = $fix->gnssAltitude;
                }
            }
            
            if (!empty($altitudesQnh)) {
                if (!isset($this->flight->OtherInformation)) {
                    $this->flight->OtherInformation = (object) [];
                }
                $this->flight->OtherInformation->minAltitude = min($altitudesQnh);
                $this->flight->OtherInformation->maxAltitude = max($altitudesQnh);
                $this->flight->OtherInformation->altitudeRange = 
                    $this->flight->OtherInformation->maxAltitude - $this->flight->OtherInformation->minAltitude;
                
                // Alias pour QNH (absolue)
                $this->flight->OtherInformation->minQnh = $this->flight->OtherInformation->minAltitude;
                $this->flight->OtherInformation->maxQnh = $this->flight->OtherInformation->maxAltitude;
                $this->flight->OtherInformation->qnhRange = $this->flight->OtherInformation->altitudeRange;
                
                // Altitude de décollage (QNH)
                if ($takeoffQnh !== null) {
                    $this->flight->OtherInformation->takeoffQnh = $takeoffQnh;
                    $this->flight->OtherInformation->takeoffAltitude = $takeoffQnh;
                }
            }
            
            // Statistiques sur les altitudes QFE (relatives)
            if (!empty($altitudesQfe)) {
                if (!isset($this->flight->OtherInformation)) {
                    $this->flight->OtherInformation = (object) [];
                }
                $this->flight->OtherInformation->minQfe = min($altitudesQfe);
                $this->flight->OtherInformation->maxQfe = max($altitudesQfe);
                $this->flight->OtherInformation->qfeRange = 
                    $this->flight->OtherInformation->maxQfe - $this->flight->OtherInformation->minQfe;
                
                // Alias
                $this->flight->OtherInformation->minAltitudeRelative = $this->flight->OtherInformation->minQfe;
                $this->flight->OtherInformation->maxAltitudeRelative = $this->flight->OtherInformation->maxQfe;
                $this->flight->OtherInformation->altitudeRelativeRange = $this->flight->OtherInformation->qfeRange;
            }
            
            if (!empty($gnssAltitudes)) {
                if (!isset($this->flight->OtherInformation)) {
                    $this->flight->OtherInformation = (object) [];
                }
                $this->flight->OtherInformation->minGnssAltitude = min($gnssAltitudes);
                $this->flight->OtherInformation->maxGnssAltitude = max($gnssAltitudes);
                $this->flight->OtherInformation->gnssAltitudeRange = 
                    $this->flight->OtherInformation->maxGnssAltitude - $this->flight->OtherInformation->minGnssAltitude;
            }
        }
        
        // Calculer la durée du vol (du premier au dernier point)
        if (isset($this->flight->Fix) && is_array($this->flight->Fix) && count($this->flight->Fix) > 0) {
            $firstFix = $this->flight->Fix[0];
            $lastFix = $this->flight->Fix[count($this->flight->Fix) - 1];
            
            if (isset($firstFix->timestamp) && isset($lastFix->timestamp)) {
                $flightDuration = $lastFix->timestamp - $firstFix->timestamp;
                
                if (!isset($this->flight->OtherInformation)) {
                    $this->flight->OtherInformation = (object) [];
                }
                    $this->flight->OtherInformation->flightDuration = $flightDuration;
                    $this->flight->OtherInformation->flightDurationFormatted = PhpIgcUtils::secondsToTime($flightDuration);
                
                // Date/heure de début et fin
                if (isset($firstFix->dateTime)) {
                    $this->flight->OtherInformation->flightStart = $firstFix->dateTime;
                }
                if (isset($lastFix->dateTime)) {
                    $this->flight->OtherInformation->flightEnd = $lastFix->dateTime;
                }
            }
        }
    }
    
    /**
     * Convertit des secondes en format hh:mm:ss
     * 
     * @param int $seconds Nombre de secondes
     * @return string Format hh:mm:ss
     */
    private function secondsToTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
    
    /**
     * Retourne l'objet flight parsé
     * 
     * @return object|null
     */
    public function getFlight(): ?object
    {
        return $this->flight ?? null;
    }
    
    /**
     * Retourne les métadonnées du vol (records uniques uniquement)
     * 
     * @return object|null
     */
    public function getMetadata(): ?object
    {
        if ($this->flight === null) {
            return null;
        }
        
        $metadata = (object) [];
        
        // Parcourir les propriétés du flight pour ne garder que les records uniques
        foreach ($this->flight as $key => $value) {
            // Si c'est un tableau, c'est un record multiple, on ne l'inclut pas
            // Si c'est un objet, c'est un record unique, on l'inclut
            if (!is_array($value)) {
                $metadata->$key = $value;
            }
        }
        
        return $metadata;
    }
    
    /**
     * Convertit l'objet flight en JSON
     * 
     * @param int $flags Options pour json_encode (par défaut JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
     * @return string|null Représentation JSON de l'objet flight
     */
    public function stringify(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): ?string
    {
        if ($this->flight === null) {
            return null;
        }
        return json_encode($this->flight, $flags);
    }
    
    /**
     * Retourne l'objet flight au format JSON
     * 
     * @return string|null Représentation JSON de l'objet flight
     */
    public function toJson(): ?string
    {
        return $this->stringify();
    }
    
    /**
     * Extrait chaque type d'enregistrement dans un fichier séparé
     * 
     * @param string $outputDirectory Répertoire de sortie pour les fichiers extraits
     * @param string|null $prefix Préfixe pour les noms de fichiers (optionnel)
     * @return array Tableau associatif avec le type d'enregistrement comme clé et le chemin du fichier comme valeur
     * @throws \RuntimeException Si le répertoire ne peut pas être créé ou si l'écriture échoue
     */
    public function rawExtract(string $outputDirectory, ?string $prefix = null): array
    {
        if (empty(trim($this->content))) {
            throw new InvalidIgcException('Le fichier IGC est vide');
        }
        
        // Créer le répertoire de sortie s'il n'existe pas
        if (!is_dir($outputDirectory)) {
            if (!mkdir($outputDirectory, 0755, true)) {
                throw new \RuntimeException("Impossible de créer le répertoire de sortie : {$outputDirectory}");
            }
        }
        
        // Préparer le préfixe
        $filePrefix = $prefix !== null ? $prefix . '_' : '';
        
        // Séparer les lignes
        $lines = explode("\n", $this->content);
        
        // Grouper les lignes par type d'enregistrement
        $recordsByType = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Ignorer les lignes vides
            if (empty($line)) {
                continue;
            }
            
            // Récupérer le type d'enregistrement (première lettre)
            $recordType = $line[0];
            
            // Vérifier que c'est une lettre majuscule valide
            if (!preg_match('/^[A-Z]$/', $recordType)) {
                continue;
            }
            
            // Ajouter la ligne au groupe correspondant
            if (!isset($recordsByType[$recordType])) {
                $recordsByType[$recordType] = [];
            }
            
            $recordsByType[$recordType][] = $line;
        }
        
        // Écrire chaque type dans un fichier séparé
        $extractedFiles = [];
        
        foreach ($recordsByType as $type => $lines) {
            $filename = $filePrefix . 'record_' . $type . '.igc';
            $filepath = rtrim($outputDirectory, '/') . '/' . $filename;
            
            $content = implode("\n", $lines) . "\n";
            
            if (file_put_contents($filepath, $content) === false) {
                throw new \RuntimeException("Impossible d'écrire le fichier : {$filepath}");
            }
            
            $extractedFiles[$type] = $filepath;
        }
        
        return $extractedFiles;
    }
    
    /**
     * Extrait chaque type d'enregistrement depuis un fichier IGC dans des fichiers séparés
     * 
     * @param string $igcFilePath Chemin vers le fichier IGC source
     * @param string $outputDirectory Répertoire de sortie pour les fichiers extraits
     * @param string|null $prefix Préfixe pour les noms de fichiers (optionnel)
     * @return array Tableau associatif avec le type d'enregistrement comme clé et le chemin du fichier comme valeur
     * @throws \RuntimeException Si le fichier source ne peut pas être lu ou si l'extraction échoue
     */
    public static function rawExtractFromFile(string $igcFilePath, string $outputDirectory, ?string $prefix = null): array
    {
        if (!file_exists($igcFilePath)) {
            throw new \RuntimeException("Le fichier IGC n'existe pas : {$igcFilePath}");
        }
        
        $content = file_get_contents($igcFilePath);
        
        if ($content === false) {
            throw new \RuntimeException("Impossible de lire le fichier IGC : {$igcFilePath}");
        }
        
        $inspector = new self($content);
        return $inspector->rawExtract($outputDirectory, $prefix);
    }
}

