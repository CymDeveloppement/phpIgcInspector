<?php

namespace Ycdev\PhpIgcInspector\Data;

/**
 * Classe pour gérer les codes d'événements IGC (Three-Letter Codes - TLC)
 * 
 * Cette classe contient tous les codes d'événements officiels utilisés dans les fichiers IGC
 * selon la spécification IGC (chapitre 7 - Three-Letter Codes).
 * 
 * Référence: IGC FILE FORMAT REFERENCE AND DEVELOPERS' GUIDE - Section 7
 * Seuls les codes TLC officiels pour les événements (Record E) sont inclus.
 */
class EventTypeCode
{
    /**
     * Mapping des codes d'événements officiels vers les types standardisés
     * Basé sur la spécification IGC chapitre 7 - Record E uniquement
     * 
     * @var array<string, string>
     */
    private static array $eventTypeMapping = [
        // Départ / Start (officiel IGC)
        'STA' => 'start',           // Start event
        
        // Arrivée / Finish (officiel IGC)
        'FIN' => 'finish',          // Finish
        
        // Turnpoint (officiel IGC)
        'TPC' => 'turnpoint',       // Turn point confirmation
        
        // Moteur / Engine (officiels IGC)
        'EON' => 'motor',           // Engine on
        'EOF' => 'motor',           // Engine off
        'EUP' => 'motor',           // Engine up
        'EDN' => 'motor',           // Engine down
        
        // Blind Flying Instrument (officiel IGC)
        'BFION' => 'function_on',   // Blind Flying Instrument ON
        'BFIOFF' => 'function_off', // Blind Flying Instrument OFF
        'BFIUN' => 'function_unknown', // Blind Flying Instrument Unknown
        
        // Camera (officiels IGC)
        'CCN' => 'camera_connect',  // Camera Connect
        'CDC' => 'camera_disconnect', // Camera Disconnect
        
        // GNSS (officiels IGC)
        'GCN' => 'gnss_connect',    // GNSS Connect
        'GDC' => 'gnss_disconnect', // GNSS Disconnect
        
        // Photo (officiel IGC)
        'PHO' => 'photo',           // Photo taken
        
        // Altimeter (officiel IGC)
        'ATS' => 'altimeter_setting', // Altimeter pressure setting
        
        // Geodetic Datum (officiel IGC)
        'CGD' => 'datum_change',    // Change of geodetic datum
        
        // Task (officiel IGC)
        'ONT' => 'on_task',         // On Task - attempting task
        
        // Low Voltage (officiel IGC)
        'LOV' => 'low_voltage',     // Low voltage
        
        // MacCready (officiel IGC)
        'MAC' => 'maccready',       // MacCready setting
        
        // Flap Position (officiel IGC)
        'FLP' => 'flap_position',  // Flap position (ex: FLP060, FLP-20)
        
        // Undercarriage / Landing Gear (officiel IGC)
        'UNDUP' => 'gear_up',       // Undercarriage UP
        'UNDDN' => 'gear_down',     // Undercarriage DOWN
        
        // Other Aircraft (officiels IGC)
        'OA1' => 'other_aircraft',  // Position of other aircraft 1
        'OA2' => 'other_aircraft',  // Position of other aircraft 2
        'OA3' => 'other_aircraft',  // Position of other aircraft 3
        
        // Événement pilote générique (officiel IGC)
        'PEV' => 'pilot_event',     // Pilot Event
    ];
    
    /**
     * Descriptions des types d'événements
     * 
     * @var array<string, string>
     */
    private static array $eventTypeDescriptions = [
        'start' => 'Départ de la tâche (STA)',
        'finish' => 'Arrivée / Fin de la tâche (FIN)',
        'turnpoint' => 'Passage de turnpoint (TPC)',
        'motor' => 'Activation/désactivation du moteur (EON/EOF/EUP/EDN)',
        'function_on' => 'Activation de fonction (BFION)',
        'function_off' => 'Désactivation de fonction (BFIOFF)',
        'function_unknown' => 'État de fonction inconnu (BFIUN)',
        'camera_connect' => 'Connexion de la caméra (CCN)',
        'camera_disconnect' => 'Déconnexion de la caméra (CDC)',
        'gnss_connect' => 'Connexion du module GNSS (GCN)',
        'gnss_disconnect' => 'Déconnexion du module GNSS (GDC)',
        'photo' => 'Photo prise (PHO)',
        'altimeter_setting' => 'Réglage de l\'altimètre (ATS)',
        'datum_change' => 'Changement de datum géodésique (CGD)',
        'on_task' => 'En tâche - tentative de tâche (ONT)',
        'low_voltage' => 'Tension basse (LOV)',
        'maccready' => 'Réglage MacCready (MAC)',
        'flap_position' => 'Position des volets (FLP)',
        'gear_up' => 'Train d\'atterrissage relevé (UNDUP)',
        'gear_down' => 'Train d\'atterrissage sorti (UNDDN)',
        'other_aircraft' => 'Position d\'un autre aéronef (OA1/OA2/OA3)',
        'pilot_event' => 'Événement pilote générique (PEV)',
        'other' => 'Autre événement',
    ];
    
    /**
     * Détecte le type d'événement à partir du code
     * 
     * @param string $eventCode Code de l'événement (TLC)
     * @return string Type d'événement standardisé
     */
    public static function detectEventType(string $eventCode): string
    {
        $code = strtoupper(trim($eventCode));
        
        // Recherche exacte dans le mapping
        if (isset(self::$eventTypeMapping[$code])) {
            return self::$eventTypeMapping[$code];
        }
        
        // Recherche par préfixe pour les codes avec variations (ex: FLP060, UNDUP, OA1, etc.)
        foreach (self::$eventTypeMapping as $pattern => $type) {
            // Vérifier si le code commence par le pattern
            if (strpos($code, $pattern) === 0) {
                return $type;
            }
        }
        
        // Recherche par expressions régulières pour les codes officiels uniquement
        // Start (officiel IGC)
        if (preg_match('/^STA/i', $code)) {
            return 'start';
        }
        
        // Finish (officiel IGC)
        if (preg_match('/^FIN/i', $code)) {
            return 'finish';
        }
        
        // Turnpoint (officiel IGC)
        if (preg_match('/^TPC/i', $code)) {
            return 'turnpoint';
        }
        
        // Engine/Motor (officiels IGC)
        if (preg_match('/^(EON|EOF|EUP|EDN)/i', $code)) {
            return 'motor';
        }
        
        // Blind Flying Instrument (officiel IGC)
        if (preg_match('/BFION/i', $code)) {
            return 'function_on';
        }
        if (preg_match('/BFIOFF/i', $code)) {
            return 'function_off';
        }
        if (preg_match('/BFIUN/i', $code)) {
            return 'function_unknown';
        }
        
        // Camera (officiels IGC)
        if (preg_match('/^CCN/i', $code)) {
            return 'camera_connect';
        }
        if (preg_match('/^CDC/i', $code)) {
            return 'camera_disconnect';
        }
        
        // GNSS (officiels IGC)
        if (preg_match('/^GCN/i', $code)) {
            return 'gnss_connect';
        }
        if (preg_match('/^GDC/i', $code)) {
            return 'gnss_disconnect';
        }
        
        // Photo (officiel IGC)
        if (preg_match('/^PHO/i', $code)) {
            return 'photo';
        }
        
        // Altimeter (officiel IGC)
        if (preg_match('/^ATS/i', $code)) {
            return 'altimeter_setting';
        }
        
        // Geodetic Datum (officiel IGC)
        if (preg_match('/^CGD/i', $code)) {
            return 'datum_change';
        }
        
        // On Task (officiel IGC)
        if (preg_match('/^ONT/i', $code)) {
            return 'on_task';
        }
        
        // Low Voltage (officiel IGC)
        if (preg_match('/^LOV/i', $code)) {
            return 'low_voltage';
        }
        
        // MacCready (officiel IGC)
        if (preg_match('/^MAC/i', $code)) {
            return 'maccready';
        }
        
        // Flap Position (officiel IGC)
        if (preg_match('/^FLP/i', $code)) {
            return 'flap_position';
        }
        
        // Undercarriage (officiel IGC)
        if (preg_match('/UNDUP/i', $code)) {
            return 'gear_up';
        }
        if (preg_match('/UNDDN/i', $code)) {
            return 'gear_down';
        }
        
        // Other Aircraft (officiels IGC)
        if (preg_match('/^OA[1-9]/i', $code)) {
            return 'other_aircraft';
        }
        
        // Pilot Event générique (officiel IGC)
        if (preg_match('/^PEV$/i', $code)) {
            return 'pilot_event';
        }
        
        return 'other';
    }
    
    /**
     * Retourne la description d'un type d'événement
     * 
     * @param string $eventType Type d'événement
     * @return string Description du type d'événement
     */
    public static function getEventTypeDescription(string $eventType): string
    {
        return self::$eventTypeDescriptions[$eventType] ?? 'Type d\'événement inconnu';
    }
    
    /**
     * Retourne tous les types d'événements disponibles
     * 
     * @return array<string> Liste des types d'événements
     */
    public static function getAvailableEventTypes(): array
    {
        return array_keys(self::$eventTypeDescriptions);
    }
    
    /**
     * Retourne tous les codes d'événements mappés pour un type donné
     * 
     * @param string $eventType Type d'événement
     * @return array<string> Liste des codes correspondants
     */
    public static function getCodesForType(string $eventType): array
    {
        $codes = [];
        foreach (self::$eventTypeMapping as $code => $type) {
            if ($type === $eventType) {
                $codes[] = $code;
            }
        }
        return $codes;
    }
    
    /**
     * Vérifie si un code d'événement est reconnu
     * 
     * @param string $eventCode Code de l'événement
     * @return bool True si le code est reconnu
     */
    public static function isRecognized(string $eventCode): bool
    {
        $type = self::detectEventType($eventCode);
        return $type !== 'other';
    }
    
    /**
     * Retourne le mapping complet des codes d'événements
     * 
     * @return array<string, string> Mapping code => type
     */
    public static function getEventTypeMapping(): array
    {
        return self::$eventTypeMapping;
    }
    
    /**
     * Retourne tous les codes TLC officiels pour les événements (Record E)
     * Basé sur la spécification IGC chapitre 7
     * 
     * @return array<string> Liste des codes TLC officiels
     */
    public static function getOfficialEventTLCs(): array
    {
        return [
            'ATS',   // Altimeter pressure setting
            'BFI',   // Blind Flying Instrument (BFION/BFIOFF/BFIUN)
            'CCN',   // Camera Connect
            'CDC',   // Camera Disconnect
            'CGD',   // Change of geodetic datum
            'EDN',   // Engine down
            'EOF',   // Engine off
            'EON',   // Engine on
            'EUP',   // Engine up
            'FIN',   // Finish
            'FLP',   // Flap position
            'GCN',   // GNSS Connect
            'GDC',   // GNSS Disconnect
            'LOV',   // Low voltage
            'MAC',   // MacCready setting
            'OA1',   // Other Aircraft 1
            'OA2',   // Other Aircraft 2
            'OA3',   // Other Aircraft 3 (et plus)
            'ONT',   // On Task
            'PEV',   // Pilot Event
            'PHO',   // Photo taken
            'STA',   // Start event
            'TPC',   // Turn point confirmation
            'UND',   // Undercarriage (UNDUP/UNDDN)
        ];
    }
}
