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
    
    protected array $format = [
        ['time', '/^B(\d{6})/', '/^\d{6}$/'],
        ['latitude', '/^(\d{7})/', '/^\d{7}$/'],
        ['latitudeNS', '/^([NS])/', '/^[NS]$/'],
        ['longitude', '/^(\d{8})/', '/^\d{8}$/'],
        ['longitudeEW', '/^([EW])/', '/^[EW]$/'],
        ['validity', '/^([AV])/', '/^[AV]$/'],
        ['pressureAltitude', '/^(\d{5})/', '/^\d{5}$/'],
        ['gnssAltitude', '/^(\d{5}|-\d{4})/', '/^-?\d{5}$/']
    ];

    public function matches(string $line): bool
    {
        return strlen($line) > 0 && $line[0] === 'B';
    }

    public function parse(): object
    {
        // Vérifier la validité du record
        $this->check();
        $data = $this->extract();

        $data['timestamp'] = strtotime(((!is_null($this->flight) && isset($this->flight->OtherInformation->date)) ? $this->flight->OtherInformation->date : date('Y-m-d')).' '.$data['time']);
        $data['dateTime'] = date('Y-m-d H:i:s', $data['timestamp']);
        $data['latitude'] = ((float) $data['latitude']) / 100000;
        if($data['latitudeNS'] === 'S') {
            $data['latitude'] = -$data['latitude'];
        }
        $data['longitude'] = ((float) $data['longitude']) / 100000;
        if($data['longitudeEW'] === 'W') {
            $data['longitude'] = -$data['longitude'];
        }
        $data['gnssAltitude'] = (int) $data['gnssAltitude'];
        $data['pressureAltitude'] = (int) $data['pressureAltitude'];
        return (object) $data;
    }

    public function check(): bool
    {
        // TODO: Implémentation de la validation spécifique
        return true;
    }
}
