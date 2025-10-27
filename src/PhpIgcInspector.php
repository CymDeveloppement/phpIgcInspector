<?php

namespace Ycdev\PhpIgcInspector;

use Ycdev\PhpIgcInspector\Exception\InvalidIgcException;
use Ycdev\PhpIgcInspector\RecordTypes\RecordTypeInterface;
use Ycdev\PhpIgcInspector\RecordTypes\AbstractRecordType;

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
            
            // Si le record est unique, stocker directement
            if ($record->isSingleRecord()) {
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
                $flight->$recordId[] = $parsedData;
            }
            
            $previousRecordType = $firstChar;
            $validLines++;
        }

        if ($validLines === 0) {
            throw new InvalidIgcException('Le fichier IGC ne contient aucune ligne valide');
        }
        
        // Stocker l'objet flight dans une propriété
        $this->flight = $flight;

        return true;
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
}

