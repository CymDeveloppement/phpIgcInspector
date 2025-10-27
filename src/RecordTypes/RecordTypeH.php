<?php

namespace Ycdev\PhpIgcInspector\RecordTypes;

use Ycdev\PhpIgcInspector\Exception\InvalidIgcException;

/**
 * Record Type H - File header
 * 
 * Format: H + Three Letter Code (TLC) + : + valeur
 * Exemples:
 * - HFDTE050822 (Date: 05/08/22)
 * - HFPLTPILOT:Mike Young (Pilot: Mike Young)
 * - HFGIDGLIDERID:D-KVMY (Glider ID: D-KVMY)
 * - HFRFWFIRMWAREVERSION:9.0 (Firmware: 9.0)
 */
class RecordTypeH extends AbstractRecordType
{
    protected array $format = [
        ['data' => 
            ['date', '/^HFDTE(\d{6})/', '/^\d{6}$/'],
            ['pilot', '/^HFPLTPILOTINCHARGE:([A-Za-z\s]+)/', '/^[A-Za-z\s]+$/'],
            ['secondPilot', '/^HFCM2CREW2:([A-Za-z\s]+)/', '/^[A-Za-z\s]+$/'],
            ['gliderType', '/^HFGTYGLIDERTYPE:([A-Za-z0-9. -]+)/', '/^[A-Za-z0-9. -]+$/'],
            ['gliderId', '/^HFGIDGLIDERID:([A-Za-z0-9-:]+)/', '/^[A-Za-z0-9-:]+$/'],
            ['firmwareVersion', '/^HFRFWFIRMWAREVERSION:([A-Za-z0-9. -]+)/', '/^[A-Za-z0-9. -]+$/'],
            ['hardwareVersion', '/^HFRHWHARDWAREVERSION:([A-Za-z0-9. -]+)/', '/^[A-Za-z0-9. -]+$/'],
            ['loggerType', '/^HFFTYFRTYPE:([A-Za-z0-9., -]+)/', '/^[A-Za-z0-9., -]+$/'],
            ['gpsManufacturer', '/^HFGPS(.+)/', '/^.+/'],
            ['gpsVersion', '/^HFGPSGPSVERSION:([A-Za-z0-9. -]+)/', '/^[A-Za-z0-9. -]+$/'],
            ['accuracy', '/^HFFXA([0-9]+)/', '/^[0-9]+$/'],
            ['pressureSensorManufacturer', '/^HFPRSPRESSALTSENSOR:([A-Za-z0-9., -]+)/', '/^[A-Za-z0-9., -]+$/'],
            ['competitionId', '/^HFCIDCOMPETITIONID:([A-Za-z0-9., -]+)/', '/^[A-Za-z0-9., -]+$/'],
            ['competitionClass', '/^HFCCLCOMPETITIONCLASS:(.+)/', '/^.+$/'],
        ]
    ];
    
    protected string $recordId = 'OtherInformation';
    protected bool $singleObject = true;

    public function matches(string $line): bool
    {
        return strlen($line) > 0 && $line[0] === 'H';
    }

    /**
     * Parse une ligne d'enregistrement
     *
     * @return mixed Données parsées (objet ou array selon le RecordType)
     * @throws InvalidIgcException Si la ligne n'est pas valide
     */
    public function parse()
    {
        // Vérifier la validité du record
        $this->check();
        
        $data = $this->extract();
        if(isset($data['date'])) {
            $data['date'] = $this->dateToIso($data['date']);
        }
        // Convertir le tableau en objet stdClass
        return (object) $data;
    }

    private function dateToIso(string $date): string
    {
        return '20'.substr($date, 4, 2).'-'.substr($date, 2, 2).'-'.substr($date, 0, 2);
    }

    public function check(): bool
    {
        // Ligne doit commencer par H
        if (strlen($this->line) < 4) {
            throw new InvalidIgcException(
                sprintf('Ligne %d : enregistrement H invalide (trop court)', $this->lineNumber)
            );
        }
        
        // Les 3 premières lettres après H doivent être des majuscules
        if (!preg_match('/^H[A-Z]{3}/', $this->line)) {
            throw new InvalidIgcException(
                sprintf('Ligne %d : code à trois lettres invalide après "H"', $this->lineNumber)
            );
        }
        
        // Si un séparateur ':' est présent, il doit y avoir des données après
        if (strpos($this->line, ':') !== false && strlen($this->line) <= 5) {
            throw new InvalidIgcException(
                sprintf('Ligne %d : enregistrement H avec ":" mais sans données', $this->lineNumber)
            );
        }
        
        return true;
    }
}
