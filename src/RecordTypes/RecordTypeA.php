<?php

namespace Ycdev\PhpIgcInspector\RecordTypes;

use Ycdev\PhpIgcInspector\Exception\InvalidIgcException;
use Ycdev\PhpIgcInspector\Data\ManufacturerCodesData;
/**
 * Record Type A - FR manufacturer and identification
 */
class RecordTypeA extends AbstractRecordType
{

    protected string $recordId = 'Manufacturer';
    
    /**
     * Le record A doit apparaître une seule fois (en première ligne)
     */
    protected bool $singleRecord = true;
    
    /**
     * Définition du format pour le record A
     * [id, regexSearch, regexValidate]
     */
    protected array $format = [
        ['manufacturerId', '/^A(.{3})/', '/^[A-Z0-9]{3}$/'],
        ['serialNumber', '/^([A-Z0-9:]{3,})(?:-|$)/', '/^[A-Z0-9:]{3,}$/'],
        ['additionalData']
    ];

    public function matches(string $line): bool
    {
        return strlen($line) > 0 && $line[0] === 'A';
    }

    public function parse(): object
    {
        // Vérifier la validité du record
        $this->check();
        
        $data = $this->extract();
        $data['manufacturerName'] = ManufacturerCodesData::getCodes()[$data['manufacturerId']];
        $data['approvedManufacturer'] = (substr($data['manufacturerId'], 0, 1) !== 'X') ? true : false;
        
        return (object) $data;
    }

    public function check(): bool
    {
        // Le record A doit être à la ligne 1
        if ($this->lineNumber !== 1) {
            throw new InvalidIgcException(
                sprintf('Le record A (manufacturer) doit être à la ligne 1, trouvé à la ligne %d', $this->lineNumber)
            );
        }
        
        // Vérifier que le code fabricant est connu (optionnel)
        $data = $this->extract();
        $manufacturerId = $data['manufacturerId'] ?? '';
        
        $codes = ManufacturerCodesData::getCodes();
        if (!empty($manufacturerId) && !isset($codes[$manufacturerId])) {
            // Pas d'exception pour un code inconnu, juste un avertissement dans le parsing
            // Le fichier peut toujours être valide avec un fabricant non enregistré
        }
        
        return true;
    }
    
    /**
     * Retourne le nom du fabricant
     * 
     * @return string|null Nom du fabricant ou null si inconnu
     */
    public function getManufacturerName(): ?string
    {
        $data = $this->extract();
        $manufacturerId = $data['manufacturerId'] ?? '';
        $codes = ManufacturerCodesData::getCodes();
        
        return $codes[$manufacturerId] ?? null;
    }

}

