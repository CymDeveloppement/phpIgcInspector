<?php

namespace Ycdev\PhpIgcInspector\RecordTypes;

use Ycdev\PhpIgcInspector\Exception\InvalidIgcException;

/**
 * Record Type B - Fix records (points GPS)
 * 
 * Format : B HHMMSS DDMMmmmN DDDMMmmmE A PPPPP GGGGG
 * - HHMMSS : Heure UTC (6 chiffres)
 * - DDMMmmm : Latitude (7 caractères)
 * - N/S : Hémisphère Nord ou Sud
 * - DDDMMmmm : Longitude (8 caractères)
 * - E/W : Hémisphère Est ou Ouest
 * - A/V : Validité du signal ('A' = 3D valide, 'V' = non valide)
 * - PPPPP : Altitude barométrique QNH (5 chiffres, en mètres, absolue)
 *           Note: Certains enregistreurs stockent la pression (hPa) au lieu de l'altitude
 *           QNH = altitude ramenée au niveau de la mer (absolue)
 *           QFE = altitude relative au décollage (calculée après parsing)
 * - GGGGG : Altitude GPS (5 chiffres, en mètres)
 */
class RecordTypeB extends AbstractRecordType
{
    protected string $recordId = 'Fix';
    protected int $maxValidSpeed = 400;

    protected array $format = [
        ['time', '/^B(\d{6})/', '/^\d{6}$/'],
        ['latitude', '/^(\d{7})/', '/^\d{7}$/'],
        ['latitudeNS', '/^([NS])/', '/^[NS]$/'],
        ['longitude', '/^(\d{8})/', '/^\d{8}$/'],
        ['longitudeEW', '/^([EW])/', '/^[EW]$/'],
        ['validity', '/^([AV])/', '/^[AV]$/'],
        ['pressureAltitude', '/^(\d{5})/', '/^\d{5}$/'],
        ['gnssAltitude', '/^(-?\d{5})/', '/^-?\d{5}$/'],
        ['fixAccuracy', '/^(\d{3})/', '/^\d{3}$/'],
        ['satellites', '/^(\d{2})/', '/^\d{2}$/'],
        ['engineNoise', '/^(\d{3})/', '/^\d{3}$/'],
    ];

    public function matches(string $line): bool
    {
        return strlen($line) > 0 && $line[0] === 'B';
    }

    public function parse(): object|null
    {
        // Vérifier la validité du record
        $this->check();
        $data = $this->extract();   
        $data['satellites'] = (int) $data['satellites'];

        //timestamp et dateTime     
        $data['timestamp'] = strtotime(((!is_null($this->flight) && isset($this->flight->OtherInformation->date)) ? $this->flight->OtherInformation->date : date('Y-m-d')).' '.$data['time']);
        $data['dateTime'] = date('Y-m-d H:i:s', $data['timestamp']);

        //latitude et longitude - Conversion IGC correcte (millièmes de minute)
        $latDegrees = (int) substr($data['latitude'], 0, 2);
        $latMinutes = (int) substr($data['latitude'], 2, 2);
        $latThousandths = (int) substr($data['latitude'], 4, 3);
        $data['latitude'] = $this->degreeToDecimal($latDegrees, $latMinutes, $latThousandths);
        
        if($data['latitudeNS'] === 'S') {
            $data['latitude'] = -$data['latitude'];
        }
        
        $lonDegrees = (int) substr($data['longitude'], 0, 3);
        $lonMinutes = (int) substr($data['longitude'], 3, 2);
        $lonThousandths = (int) substr($data['longitude'], 5, 3);
        $data['longitude'] = $this->degreeToDecimal($lonDegrees, $lonMinutes, $lonThousandths);
        
        if($data['longitudeEW'] === 'W') {
            $data['longitude'] = -$data['longitude'];
        }
        
        //altitudes
        $data['gnssAltitude'] = isset($data['gnssAltitude']) && $data['gnssAltitude'] !== '' 
            ? (int) $data['gnssAltitude'] 
            : null;
        
        // Altitude barométrique QNH (absolue) - extraite depuis le champ PPPPP
        // Note: Dans le format IGC, PPPPP peut être soit la pression (hPa) soit l'altitude (m)
        // La plupart des enregistreurs modernes stockent directement l'altitude en mètres
        // QNH = altitude barométrique ramenée au niveau de la mer (absolue)
        $data['pressureAltitude'] = isset($data['pressureAltitude']) && $data['pressureAltitude'] !== '' 
            ? (int) $data['pressureAltitude'] 
            : null;
        
        // Alias pour plus de clarté
        $data['barometricAltitude'] = $data['pressureAltitude'];
        $data['qnh'] = $data['pressureAltitude']; // QNH = altitude absolue
        
        //fixRecordCount
        if(!is_null($this->flight)) {
            if(!isset($this->flight->OtherInformation->fixRecordCount)) {
                $this->flight->OtherInformation->fixRecordCount = 1;
            } else {
                $this->flight->OtherInformation->fixRecordCount++;
            }

            // distance from last record
            if(isset($this->flight->Fix) && count($this->flight->Fix) > 0) {
                
                $lastRecord = $this->flight->Fix[count($this->flight->Fix) - 1];
                $data['distanceFromLastRecord'] = $this->distanceGps($lastRecord->latitude, $lastRecord->longitude, $data['latitude'], $data['longitude']);
                $data['speed'] = $this->speedGps($data['distanceFromLastRecord'], $data['timestamp'] - $lastRecord->timestamp);

                if(!$this->isCorrectRecord($data)) {
                    return null;
                }

                if(!isset($this->flight->OtherInformation->totalDistance)) {
                    $this->flight->OtherInformation->totalDistance = 0;
                }
                if(!isset($this->flight->OtherInformation->maxSpeed)) {
                    $this->flight->OtherInformation->maxSpeed = 0;
                }
                if(!isset($this->flight->OtherInformation->totalDistance)) {
                    $this->flight->OtherInformation->totalDistance = 0;
                }
                if(!isset($this->flight->OtherInformation->totalTime)) {
                    $this->flight->OtherInformation->totalTime = 0;
                }
                $this->flight->OtherInformation->totalTime += $data['timestamp'] - $lastRecord->timestamp;
                $this->flight->OtherInformation->totalDistance += $data['distanceFromLastRecord'];
                
                $this->flight->OtherInformation->maxSpeed = max($this->flight->OtherInformation->maxSpeed, $data['speed']);
            } else {
                $data['distanceFromLastRecord'] = 0;
                $data['speed'] = 0;
            }
        }

        if(!$this->isCorrectRecord($data)) {
            return null;
        }

        return (object) $data;
    }

    public function check(): bool
    {
        // TODO: Implémentation de la validation spécifique
        return true;
    }

    /**
     * Calcule la distance entre deux points GPS en utilisant la formule de Haversine
     * Plus stable numériquement que la formule acos() pour de petites distances
     * 
     * @param float $latitude1 Latitude du premier point (degrés décimaux)
     * @param float $longitude1 Longitude du premier point (degrés décimaux)
     * @param float $latitude2 Latitude du deuxième point (degrés décimaux)
     * @param float $longitude2 Longitude du deuxième point (degrés décimaux)
     * @return int Distance en mètres (arrondie)
     */
    private function distanceGps($latitude1, $longitude1, $latitude2, $longitude2)
    {
        // Points identiques
        if($latitude1 == $latitude2 && $longitude1 == $longitude2) {
            return 0;
        }
        
        // Rayon de la Terre en mètres (WGS84)
        $earthRadius = 6378137.0;
        
        // Conversion en radians
        $lat1 = deg2rad($latitude1);
        $lon1 = deg2rad($longitude1);
        $lat2 = deg2rad($latitude2);
        $lon2 = deg2rad($longitude2);
        
        // Différences
        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;
        
        // Formule de Haversine (plus stable numériquement)
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        
        // Protection contre NaN et valeurs infinies
        if (is_nan($distance) || is_infinite($distance)) {
            return 0;
        }
        
        return (int) round($distance);
    }

    private function speedGps($distance, $time)
    {
        // vitesse en km/h
        // distance en mètres, temps en secondes
        // Conversion : m/s * 3.6 = km/h
        if ($time == 0 || $distance == 0) {
            return 0;
        }
        $speed = ($distance / $time) * 3.6;
        return round($speed, 2);
    }

    private function isCorrectRecord(array $data): bool
    {
        if(is_nan($data['speed'])) {
            $this->recordError('Speed is NaN');
            return false;
        }

        if(is_infinite($data['speed'])) {
            $this->recordError('Speed is infinite');
            return false;
        }

        if(!is_null($this->flight) && isset($data['speed']) && $data['speed'] > $this->maxValidSpeed) {
            $this->recordError('Speed too high: '.$data['speed'].' km/h');
            return false;
        }
        return true;
    }

    /**
     * Convertit les coordonnées IGC (DDMMmmm) en degrés décimaux
     * 
     * Format IGC : DDMMmmm où mmm sont des millièmes de minute (pas des secondes)
     * 
     * @param int $degrees Degrés
     * @param int $minutes Minutes
     * @param int $thousandths Millièmes de minute (mmm)
     * @return float Degrés décimaux
     */
    private function degreeToDecimal($degrees, $minutes, $thousandths)
    {
        // Conversion correcte : mmm représente des millièmes de minute
        // Minutes décimales = MM + mmm/1000
        // Degrés décimaux = DD + (MM + mmm/1000) / 60
        $decimalMinutes = $minutes + ($thousandths / 1000);
        return $degrees + ($decimalMinutes / 60);
    }
}
