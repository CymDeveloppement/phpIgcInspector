<?php

namespace Ycdev\PhpIgcInspector\RecordTypes;

use Ycdev\PhpIgcInspector\Exception\InvalidIgcException;

/**
 * Classe abstraite pour tous les types d'enregistrements IGC
 * 
 * Le format est défini dans la propriété $format de chaque classe :
 * @property array $format Format de définition : [id, regexSearch, regexValidate]
 *   - id : Identifiant du champ (clé dans le tableau de retour)
 *   - regexSearch : Regex pour extraire la valeur du champ de la ligne
 *   - regexValidate : Regex pour valider le contenu de la valeur extraite
 * 
 * Exemple de format :
 * protected array $format = [
 *     ['manufacturer_id', '/^A(.{6})/', '/^[A-Z0-9]{6}$/'],
 *     ['serial_number', '/^([A-Z0-9]{3,}-?)/', '/^[A-Z0-9]{3,}-?$/']
 * ];
 */
abstract class AbstractRecordType implements RecordTypeInterface
{
    protected string $line;
    protected int $lineNumber;
    protected ?string $previousRecordType;
    protected ?object $flight = null;
    
    /**
     * Si true, ce type d'enregistrement ne doit apparaître qu'une seule fois dans le fichier
     */
    protected bool $singleRecord = false;
    
    /**
     * Si true, les enregistrements multiples doivent être fusionnés dans un seul objet
     * au lieu d'être stockés dans un tableau
     */
    protected bool $singleObject = false;
    
    /**
     * Si true, ajouter le champ 'raw' avec la ligne complète dans les données parsées
     */
    protected bool $withRaw = true;
    
    /**
     * Si true, cet enregistrement sera ignoré lors du parsing (ne sera pas ajouté à l'objet flight)
     */
    protected bool $ignoreRecord = false;
    
    /**
     * Identifiant du type d'enregistrement (ex: 'Manufacturer', 'Fix', etc.)
     */
    protected string $recordId = 'Record';

    /**
     * Constructeur
     * 
     * @param string $line Ligne d'enregistrement
     * @param int $lineNumber Numéro de la ligne
     * @param string|null $previousRecordType Type d'enregistrement précédent
     * @param bool $withRaw Si true, ajouter le champ 'raw' dans les données parsées
     * @param object|null $flight Pointeur vers l'objet flight complet (pour écriture directe si nécessaire)
     */
    public function __construct(string $line, int $lineNumber, ?string $previousRecordType = null, bool $withRaw = true, ?object $flight = null)
    {
        $this->line = $line;
        $this->lineNumber = $lineNumber;
        $this->previousRecordType = $previousRecordType;
        $this->withRaw = $withRaw;
        $this->flight = $flight;
    }

    /**
     * Vérifie si une ligne correspond à ce type d'enregistrement
     *
     * @param string $line Ligne à vérifier
     * @return bool True si la ligne correspond
     */
    abstract public function matches(string $line): bool;

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
        
        // Convertir le tableau en objet stdClass
        return (object) $data;
    }

    /**
     * Vérifie la validité d'une ligne d'enregistrement
     *
     * @return bool True si la ligne est valide
     * @throws InvalidIgcException Si la ligne n'est pas valide
     */
    abstract public function check(): bool;

    /**
     * Retourne le numéro de ligne
     */
    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    /**
     * Retourne le type d'enregistrement précédent
     */
    public function getPreviousRecordType(): ?string
    {
        return $this->previousRecordType;
    }

    /**
     * Retourne la ligne
     */
    public function getLine(): string
    {
        return $this->line;
    }
    
    /**
     * Retourne si ce type d'enregistrement ne doit apparaître qu'une fois
     */
    public function isSingleRecord(): bool
    {
        return $this->singleRecord;
    }
    
    /**
     * Retourne si les enregistrements multiples doivent être fusionnés dans un seul objet
     */
    public function isSingleObject(): bool
    {
        return $this->singleObject;
    }
    
    /**
     * Retourne si cet enregistrement doit être ignoré
     */
    public function isIgnoreRecord(): bool
    {
        return $this->ignoreRecord;
    }
    
    /**
     * Retourne l'identifiant du type d'enregistrement
     */
    public function getRecordId(): string
    {
        return $this->recordId;
    }

    /**
     * Extrait les valeurs de la ligne selon le format défini
     * 
     * @param bool $validate Si true, valide les données extraites
     * @return array Tableau associatif avec les valeurs extraites [nom_champ => valeur]
     * @throws InvalidIgcException Si le format n'est pas respecté et que validate=true
     */
    public function extract(bool $validate = true): array
    {
        $data = [];
        $offset = 0; // Position dans la ligne
        
        foreach ($this->format as $index => $field) {
            $fieldName = is_string($index) ? $index : null;
            
            // Cas 1 : Format simple [id] - extraire tout le reste (seulement si pas de clé string)
            if (count($field) === 1 && is_array($field) && isset($field[0]) && empty($fieldName) && !is_array($field[0])) {
                $id = $field[0];
                $remainingLine = substr($this->line, $offset);
                $data[$id] = $remainingLine;
                break; // On arrête après avoir extrait les données brutes
            }
            
            // Cas 2 : Tableau associatif contenant plusieurs formats possibles
            // Exemple: ['data' => [['date', '/regex/', '/val/'], ['pilot', '/regex/', '/val/']]]
            // Détecter si c'est un tableau avec des clés string
            $hasStringKeys = is_array($field) && count(array_filter(array_keys($field), 'is_string')) > 0;
            
            if (is_array($field) && ($fieldName || $hasStringKeys)) {
                $matched = false;
                $matches = [];
                
                // Tester chaque format depuis le début de la ligne
                foreach ($field as $subField) {
                    if (count($subField) === 3) {
                        [$id, $regexSearch, $regexValidate] = $subField;
                        
                        // Tester la regex sur toute la ligne
                        if ($regexSearch && preg_match($regexSearch, $this->line, $matches)) {
                            $value = $matches[1] ?? '';
                            $data[$id] = $value;
                            $matched = true;
                            break; // Premier match gagnant
                        }
                    }
                }
                
                if (!$matched) {
                    // Aucun format ne correspond, stocker null
                    $data[$fieldName] = null;
                }
                continue;
            }
            
            // Cas 3 : Format complet [id, regexSearch, regexValidate]
            if (count($field) === 3) {
                [$id, $regexSearch, $regexValidate] = $field;
                $matches = [];
                
                // Si pas de regexSearch, ignorer ce champ
                if (empty($regexSearch)) {
                    $data[$id] = '';
                    continue;
                }
                
                // Adapter la regex pour chercher à partir de $offset
                $remainingLine = substr($this->line, $offset);
                
                if ($regexSearch && preg_match($regexSearch, $remainingLine, $matches)) {
                    $value = $matches[1] ?? '';
                    $data[$id] = $value;
                    
                    // Avancer l'offset de la longueur du match complet
                    $consumed = strlen($matches[0]);
                    $offset += $consumed;
                } else {
                    $data[$id] = null;
                }
            }
        }

        // Vérifier le format des données extraites si demandé
        if ($validate) {
            $this->checkFormat($data);
        }
        
        // Ajouter la ligne complète dans 'raw' si demandé
        if ($this->withRaw) {
            $data['raw'] = $this->line;
        }

        return $data;
    }

    /**
     * Vérifie que la chaîne respecte le format défini
     * 
     * @param array|null $data Données à vérifier (optionnel)
     * @return bool True si la chaîne est conforme au format
     * @throws InvalidIgcException Si la chaîne ne respecte pas le format
     */
    public function checkFormat(?array $data = null): bool
    {
        if ($data === null) {
            $data = $this->extract(false);
        }
        
        foreach ($this->format as $index => $field) {
            $fieldName = is_string($index) ? $index : null;
            
            // Cas 1 : Format simple [id] - pas de validation
            if (count($field) === 1 && is_array($field) && isset($field[0])) {
                continue;
            }
            
            // Cas 2 : Tableau associatif avec plusieurs formats possibles
            if (is_array($field) && !empty($fieldName)) {
                foreach ($field as $subField) {
                    if (count($subField) === 3) {
                        [$id, $regexSearch, $regexValidate] = $subField;
                        $value = $data[$id] ?? '';
                        
                        // Ignorer la validation si le champ est vide
                        if (empty($value)) {
                            continue;
                        }
                        
                        // Vérifier le contenu avec regexValidate si défini
                        if ($regexValidate && !empty($value) && !preg_match($regexValidate, $value)) {
                            throw new InvalidIgcException(
                                sprintf(
                                    'Ligne %d : champ "%s" a un format invalide ("%s")',
                                    $this->lineNumber,
                                    $id,
                                    $value
                                )
                            );
                        }
                    }
                }
                continue;
            }
            
            // Cas 3 : Format complet [id, regexSearch, regexValidate]
            if (count($field) === 3) {
                [$id, $regexSearch, $regexValidate] = $field;
                $value = $data[$id] ?? '';
                
                // Ignorer la validation si le champ est vide
                if (empty($value)) {
                    continue;
                }
                
                // Vérifier le contenu avec regexValidate si défini
                if ($regexValidate && !empty($value) && !preg_match($regexValidate, $value)) {
                    throw new InvalidIgcException(
                        sprintf(
                            'Ligne %d : champ "%s" a un format invalide ("%s")',
                            $this->lineNumber,
                            $id,
                            $value
                        )
                    );
                }
            }
        }
        
        return true;
    }
}

