<?php

namespace Ycdev\PhpIgcInspector\RecordTypes;

use Ycdev\PhpIgcInspector\Exception\InvalidIgcException;

/**
 * Record Type K - Extension data
 */
class RecordTypeK extends AbstractRecordType
{
    protected array $format = [0 => ["Record", 0, 0, "", ""]];

    public function matches(string $line): bool
    {
        return strlen($line) > 0 && $line[0] === 'K';
    }

    // parse() hérite de AbstractRecordType qui ajoute automatiquement 'raw'

    public function check(): bool
    {
        // TODO: Implémentation de la validation
        return true;
    }
}
