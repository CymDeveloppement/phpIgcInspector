<?php

namespace Ycdev\PhpIgcInspector\RecordTypes;

use Ycdev\PhpIgcInspector\Exception\InvalidIgcException;

/**
 * Record Type I - List of extension data included at end of each fix B record
 */
class RecordTypeI extends AbstractRecordType
{
    protected array $format = [0 => ["Record", 0, 0, "", ""]];

    public function matches(string $line): bool
    {
        return strlen($line) > 0 && $line[0] === 'I';
    }

    // parse() hérite de AbstractRecordType qui ajoute automatiquement 'raw'

    public function check(): bool
    {
        // TODO: Implémentation de la validation
        return true;
    }
}
