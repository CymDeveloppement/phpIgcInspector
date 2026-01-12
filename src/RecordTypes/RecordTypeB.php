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
 * - PPPPP : Pression barométrique (5 chiffres)
 * - GGGGG : Altitude GPS (5 chiffres)
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
        ['gnssAltitude', '/^(\d{5}|-\d{4})/', '/^-?\d{5}$/'],
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

        //latitude et longitude
        var_dump($data['latitude']);
        $data['latitude'] = $this->degreeToDecimal((int) substr($data['latitude'], 0, 2), (int) substr($data['latitude'], 2, 2), (int) substr($data['latitude'], 4, 3));
        var_dump($data['latitude']);
        if($data['latitudeNS'] === 'S') {
            $data['latitude'] = -$data['latitude'];
        }
        $data['longitude'] = $this->degreeToDecimal((int) substr($data['longitude'], 0, 3), (int) substr($data['longitude'], 3, 2), (int) substr($data['longitude'], 5, 3));
        if($data['longitudeEW'] === 'W') {
            $data['longitude'] = -$data['longitude'];
        }
        //altitudes
        $data['gnssAltitude'] = (int) $data['gnssAltitude'];
        $data['pressureAltitude'] = (int) $data['pressureAltitude'];
        
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

    private function distanceGps($latitude1, $longitude1, $latitude2, $longitude2)
    {
        if($latitude1 == $latitude2 && $longitude1 == $longitude2) {
            return 0;
        }
        //distance en mètres
        $lat1 = deg2rad($latitude1);
        $lon1 = deg2rad($longitude1);
        $lat2 = deg2rad($latitude2);
        $lon2 = deg2rad($longitude2);
        $dist = acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($lon2 - $lon1)) * 6371000;
        return round($dist);
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

    private function degreeToDecimal($degree, $minutes, $seconds)
    {
        return $degree + $minutes / 60 + $seconds / 3600;
    }
}
