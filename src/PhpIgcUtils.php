<?php

namespace Ycdev\PhpIgcInspector;

/**
 * Classe utilitaire pour les opérations courantes sur les fichiers IGC
 * 
 * Cette classe contient des fonctions utilitaires réutilisables pour :
 * - Calcul de distances GPS
 * - Conversions de coordonnées
 * - Formatage de données
 * - Autres opérations communes
 */
class PhpIgcUtils
{
    /**
     * Rayon de la Terre en mètres (WGS84)
     */
    private const EARTH_RADIUS_WGS84 = 6378137.0;
    
    /**
     * Calcule la distance entre deux points GPS en utilisant la formule de Haversine
     * 
     * Cette méthode utilise la formule de Haversine qui est plus stable numériquement
     * que la formule basée sur acos() pour de petites distances.
     * 
     * @param float $latitude1 Latitude du premier point (degrés décimaux)
     * @param float $longitude1 Longitude du premier point (degrés décimaux)
     * @param float $latitude2 Latitude du deuxième point (degrés décimaux)
     * @param float $longitude2 Longitude du deuxième point (degrés décimaux)
     * @return float Distance en mètres
     */
    public static function calculateDistance(
        float $latitude1,
        float $longitude1,
        float $latitude2,
        float $longitude2
    ): float {
        // Points identiques
        if ($latitude1 == $latitude2 && $longitude1 == $longitude2) {
            return 0.0;
        }
        
        // Conversion en radians
        $lat1 = deg2rad($latitude1);
        $lon1 = deg2rad($longitude1);
        $lat2 = deg2rad($latitude2);
        $lon2 = deg2rad($longitude2);
        
        // Différences
        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;
        
        // Formule de Haversine
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = self::EARTH_RADIUS_WGS84 * $c;
        
        // Protection contre NaN et valeurs infinies
        if (is_nan($distance) || is_infinite($distance)) {
            return 0.0;
        }
        
        return $distance;
    }
    
    /**
     * Calcule la distance entre deux points GPS (alias pour calculateDistance)
     * 
     * @param float $latitude1 Latitude du premier point (degrés décimaux)
     * @param float $longitude1 Longitude du premier point (degrés décimaux)
     * @param float $latitude2 Latitude du deuxième point (degrés décimaux)
     * @param float $longitude2 Longitude du deuxième point (degrés décimaux)
     * @return float Distance en mètres
     */
    public static function calculateProximity(
        float $latitude1,
        float $longitude1,
        float $latitude2,
        float $longitude2
    ): float {
        return self::calculateDistance($latitude1, $longitude1, $latitude2, $longitude2);
    }
    
    /**
     * Convertit des coordonnées IGC (DDMMmmm) en degrés décimaux
     * 
     * Format IGC : DDMMmmm pour la latitude, DDDMMmmm pour la longitude
     * où mmm représente des millièmes de minute (pas des secondes)
     * 
     * @param int $degrees Degrés (DD ou DDD)
     * @param int $minutes Minutes (MM)
     * @param int $thousandths Millièmes de minute (mmm)
     * @return float Coordonnée en degrés décimaux
     */
    public static function igcToDecimal(int $degrees, int $minutes, int $thousandths): float
    {
        // Conversion correcte : mmm représente des millièmes de minute
        // Minutes décimales = MM + mmm/1000
        // Degrés décimaux = DD + (MM + mmm/1000) / 60
        $decimalMinutes = $minutes + ($thousandths / 1000);
        return $degrees + ($decimalMinutes / 60);
    }
    
    /**
     * Convertit des degrés décimaux en format IGC (DDMMmmm)
     * 
     * @param float $decimalDegrees Coordonnée en degrés décimaux
     * @param bool $isLongitude True si c'est une longitude (DDDMMmmm), false pour latitude (DDMMmmm)
     * @return array{degrees: int, minutes: int, thousandths: int} Tableau avec degrees, minutes, thousandths
     */
    public static function decimalToIgc(float $decimalDegrees, bool $isLongitude = false): array
    {
        $degrees = (int) floor(abs($decimalDegrees));
        $decimalMinutes = (abs($decimalDegrees) - $degrees) * 60;
        $minutes = (int) floor($decimalMinutes);
        $thousandths = (int) round(($decimalMinutes - $minutes) * 1000);
        
        // Ajuster si les millièmes dépassent 999
        if ($thousandths >= 1000) {
            $minutes += 1;
            $thousandths = 0;
        }
        
        // Ajuster si les minutes dépassent 59
        if ($minutes >= 60) {
            $degrees += 1;
            $minutes = 0;
        }
        
        return [
            'degrees' => $degrees,
            'minutes' => $minutes,
            'thousandths' => $thousandths
        ];
    }
    
    /**
     * Convertit des secondes en format hh:mm:ss
     * 
     * @param int $seconds Nombre de secondes
     * @return string Temps formaté (hh:mm:ss)
     */
    public static function secondsToTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
    
    /**
     * Convertit un temps formaté (hh:mm:ss) en secondes
     * 
     * @param string $timeString Temps au format hh:mm:ss ou HHMMSS
     * @return int Nombre de secondes
     */
    public static function timeToSeconds(string $timeString): int
    {
        // Format hh:mm:ss
        if (preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $timeString, $matches)) {
            $hours = (int) $matches[1];
            $minutes = (int) $matches[2];
            $seconds = (int) $matches[3];
            return ($hours * 3600) + ($minutes * 60) + $seconds;
        }
        
        // Format HHMMSS
        if (preg_match('/^(\d{2})(\d{2})(\d{2})$/', $timeString, $matches)) {
            $hours = (int) $matches[1];
            $minutes = (int) $matches[2];
            $seconds = (int) $matches[3];
            return ($hours * 3600) + ($minutes * 60) + $seconds;
        }
        
        return 0;
    }
    
    /**
     * Formate une distance en mètres avec unité
     * 
     * @param float $distance Distance en mètres
     * @param int $decimals Nombre de décimales (défaut: 2)
     * @return string Distance formatée (ex: "125.43 km" ou "45.67 m")
     */
    public static function formatDistance(float $distance, int $decimals = 2): string
    {
        if ($distance >= 1000) {
            $km = round($distance / 1000, $decimals);
            return number_format($km, $decimals, '.', ' ') . ' km';
        } else {
            return number_format($distance, $decimals, '.', ' ') . ' m';
        }
    }
    
    /**
     * Formate une vitesse en km/h
     * 
     * @param float $speed Vitesse en km/h
     * @param int $decimals Nombre de décimales (défaut: 2)
     * @return string Vitesse formatée (ex: "125.43 km/h")
     */
    public static function formatSpeed(float $speed, int $decimals = 2): string
    {
        return number_format($speed, $decimals, '.', ' ') . ' km/h';
    }
    
    /**
     * Calcule la vitesse entre deux points GPS
     * 
     * @param float $latitude1 Latitude du premier point
     * @param float $longitude1 Longitude du premier point
     * @param int $timestamp1 Timestamp du premier point
     * @param float $latitude2 Latitude du deuxième point
     * @param float $longitude2 Longitude du deuxième point
     * @param int $timestamp2 Timestamp du deuxième point
     * @return float|null Vitesse en km/h, ou null si le temps est invalide
     */
    public static function calculateSpeed(
        float $latitude1,
        float $longitude1,
        int $timestamp1,
        float $latitude2,
        float $longitude2,
        int $timestamp2
    ): ?float {
        $distance = self::calculateDistance($latitude1, $longitude1, $latitude2, $longitude2);
        $timeDiff = $timestamp2 - $timestamp1;
        
        if ($timeDiff <= 0) {
            return null;
        }
        
        // Vitesse en m/s, puis conversion en km/h
        $speedMs = $distance / $timeDiff;
        return $speedMs * 3.6;
    }
    
    /**
     * Valide une liste de points en vérifiant s'ils sont passés à proximité dans un fichier IGC
     * 
     * Pour chaque point de la liste, cette fonction vérifie s'il existe au moins un point GPS
     * dans le fichier IGC qui passe à une distance inférieure ou égale à la distance spécifiée.
     * 
     * @param array $points Liste de points à valider. Chaque point doit être un objet ou un tableau
     *                      associatif contenant les clés 'latitude' et 'longitude' (en degrés décimaux).
     *                      Exemple: [['latitude' => 51.1883, 'longitude' => -1.0285], ...]
     *                      ou [(object)['latitude' => 51.1883, 'longitude' => -1.0285], ...]
     * @param float $distance Distance maximale en mètres pour considérer qu'un point est validé
     * @param string $igcContent Contenu du fichier IGC à analyser
     * @param bool $includeIgcObject Si true, inclut l'objet IGC parsé dans le résultat (défaut: false)
     * @return object Objet contenant les résultats de validation pour chaque point :
     *                - validatedPoints: tableau des points validés avec leurs détails
     *                - invalidatedPoints: tableau des points non validés
     *                - allValidated: booléen indiquant si tous les points sont validés
     *                - validatedCount: nombre de points validés
     *                - totalPoints: nombre total de points
     *                - distance: distance maximale utilisée pour la validation
     *                - igcObject: objet IGC parsé (uniquement si $includeIgcObject est true)
     * @throws \Exception Si le fichier IGC ne peut pas être parsé ou si les points sont invalides
     */
    public static function validatePointsProximity(
        array $points,
        float $distance,
        string $igcContent,
        bool $includeIgcObject = false
    ): object {
        // Parser le fichier IGC
        $inspector = new PhpIgcInspector($igcContent);
        
        try {
            $inspector->validate();
        } catch (\Exception $e) {
            throw new \Exception("Impossible de parser le fichier IGC : " . $e->getMessage(), 0, $e);
        }
        
        $flight = $inspector->getFlight();
        
        if ($flight === null || !isset($flight->Fix) || !is_array($flight->Fix) || empty($flight->Fix)) {
            throw new \Exception("Le fichier IGC ne contient aucun point GPS (Fix)");
        }
        
        $validatedPoints = [];
        $invalidatedPoints = [];
        
        // Parcourir chaque point de la liste
        foreach ($points as $index => $point) {
            // Extraire latitude et longitude du point
            $pointLat = null;
            $pointLon = null;
            
            if (is_object($point)) {
                $pointLat = $point->latitude ?? null;
                $pointLon = $point->longitude ?? null;
            } elseif (is_array($point)) {
                $pointLat = $point['latitude'] ?? null;
                $pointLon = $point['longitude'] ?? null;
            }
            
            // Vérifier que le point a des coordonnées valides
            if ($pointLat === null || $pointLon === null || 
                !is_numeric($pointLat) || !is_numeric($pointLon)) {
                $invalidatedPoints[] = (object) [
                    'index' => $index,
                    'point' => $point,
                    'reason' => 'Coordonnées invalides ou manquantes'
                ];
                continue;
            }
            
            $pointLat = (float) $pointLat;
            $pointLon = (float) $pointLon;
            
            // Chercher le point GPS le plus proche dans le fichier IGC
            $minDistance = null;
            $closestFix = null;
            $closestFixIndex = null;
            
            foreach ($flight->Fix as $fixIndex => $fix) {
                if (!isset($fix->latitude) || !isset($fix->longitude)) {
                    continue;
                }
                
                $fixDistance = self::calculateDistance(
                    $pointLat,
                    $pointLon,
                    $fix->latitude,
                    $fix->longitude
                );
                
                if ($minDistance === null || $fixDistance < $minDistance) {
                    $minDistance = $fixDistance;
                    $closestFix = $fix;
                    $closestFixIndex = $fixIndex;
                }
            }
            
            // Vérifier si le point est validé (distance <= distance maximale)
            if ($minDistance !== null && $minDistance <= $distance) {
                $validatedPoint = (object) [
                    'index' => $index,
                    'point' => is_object($point) ? $point : (object) $point,
                    'validated' => true,
                    'minDistance' => round($minDistance, 2),
                    'closestFixIndex' => $closestFixIndex,
                    'closestFix' => $closestFix
                ];
                
                // Ajouter des informations supplémentaires si disponibles
                if (isset($closestFix->dateTime)) {
                    $validatedPoint->validatedAt = $closestFix->dateTime;
                }
                if (isset($closestFix->timestamp)) {
                    $validatedPoint->validatedAtTimestamp = $closestFix->timestamp;
                }
                
                $validatedPoints[] = $validatedPoint;
            } else {
                $invalidatedPoint = (object) [
                    'index' => $index,
                    'point' => is_object($point) ? $point : (object) $point,
                    'validated' => false,
                    'minDistance' => $minDistance !== null ? round($minDistance, 2) : null,
                    'reason' => $minDistance === null ? 'Aucun point GPS trouvé dans le fichier IGC' : 'Distance trop grande'
                ];
                
                if ($minDistance !== null && $closestFix !== null) {
                    $invalidatedPoint->closestFixIndex = $closestFixIndex;
                    $invalidatedPoint->closestFix = $closestFix;
                }
                
                $invalidatedPoints[] = $invalidatedPoint;
            }
        }
        
        // Construire l'objet de résultat
        $result = (object) [
            'validatedPoints' => $validatedPoints,
            'invalidatedPoints' => $invalidatedPoints,
            'allValidated' => empty($invalidatedPoints),
            'validatedCount' => count($validatedPoints),
            'totalPoints' => count($points),
            'distance' => $distance
        ];
        
        // Ajouter l'objet IGC si demandé
        if ($includeIgcObject) {
            $result->igcObject = $flight;
        }
        
        return $result;
    }
}
