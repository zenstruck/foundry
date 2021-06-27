<?php

namespace Zenstruck\Foundry\Bundle\Extractor;

class DoctrineTypes
{
    public const DOCTRINE_TYPES = [
        'ARRAY' => '[]',
        'ASCII_STRING' => 'self::faker()->text()',
        'BIGINT' => 'self::faker()->randomNumber()',
        'BINARY' => '// @TODO add Binary manualy',
        'BLOB' => 'self::faker()->text()',
        'BOOLEAN' => 'self::faker()->boolean()',
        'DATE_MUTABLE' => 'self::faker()->datetime()',
        'DATE_IMMUTABLE' => 'self::faker()->datetime()',
        'DATEINTERVAL' => '// @TODO add Dateinterval manualy',
        'DATETIME_MUTABLE' => 'self::faker()->datetime()',
        'DATETIME_IMMUTABLE' => 'self::faker()->datetime()',
        'DATETIMETZ_MUTABLE' => 'self::faker()->datetime()',
        'DATETIMETZ_IMMUTABLE' => 'self::faker()->datetime()',
        'DECIMAL' => 'self::faker()->randomFloat()',
        'FLOAT' => 'self::faker()->randomFloat()',
        'GUID' => '// @TODO add GUID manualy',
        'INTEGER' => 'self::faker()->randomNumber()',
        'JSON' => '[]',
        'JSON_ARRAY' => '[]',
        'OBJECT' => '// @TODO add OBJECT manualy',
        'SIMPLE_ARRAY' => '[]',
        'SMALLINT' => 'self::faker()->randomNumber(1, 32767)',
        'STRING' => 'self::faker()->text()',
        'TEXT' => 'self::faker()->text()',
        'TIME_MUTABLE' => 'self::faker()->datetime()',
        'TIME_IMMUTABLE' => 'self::faker()->datetime()',
    ];

    /**
     * Checks if exists support for a type.
     */
    public static function hasType(string $name): bool
    {
        return \in_array(\mb_strtoupper($name), self::DOCTRINE_TYPES);
    }
}
