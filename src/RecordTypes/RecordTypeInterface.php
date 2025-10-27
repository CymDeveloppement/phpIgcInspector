<?php

namespace Ycdev\PhpIgcInspector\RecordTypes;

use Ycdev\PhpIgcInspector\Exception\InvalidIgcException;

/**
 * Interface pour tous les types d'enregistrements IGC
 */
interface RecordTypeInterface
{
    /**
     * Vérifie si une ligne correspond à ce type d'enregistrement
     *
     * @param string $line Ligne à vérifier
     * @return bool True si la ligne correspond
     */
    public function matches(string $line): bool;

    /**
     * Parse une ligne d'enregistrement
     *
     * @return mixed Données parsées (objet ou array selon le RecordType)
     * @throws InvalidIgcException Si la ligne n'est pas valide
     */
    public function parse();

    /**
     * Vérifie la validité d'une ligne d'enregistrement
     *
     * @return bool True si la ligne est valide
     * @throws InvalidIgcException Si la ligne n'est pas valide
     */
    public function check(): bool;
}

