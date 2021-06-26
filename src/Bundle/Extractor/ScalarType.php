<?php

namespace Zenstruck\Foundry\Bundle\Extractor;

class ScalarType
{
    /**
     * There are 4 scalar data types in PHP
     * boolean
     * integer
     * float
     * string.
     */
    public static function isScalarType(string $value): bool
    {
        if ('boolean' === $value
            || 'integer' === $value
            || 'float' === $value
            || 'string' === $value) {
            return true;
        }

        return false;
    }
}
