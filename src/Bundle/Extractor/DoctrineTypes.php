<?php


namespace Zenstruck\Foundry\Bundle\Extractor;


class DoctrineTypes
{
    const DOCTRINE_TYPES = [
        'ARRAY',
        'ASCII_STRING',
        'BIGINT',
        'BINARY',
        'BLOB',
        'BOOLEAN',
        'DATE_MUTABLE',
        'DATE_IMMUTABLE',
        'DATEINTERVAL',
        'DATETIME_MUTABLE',
        'DATETIME_IMMUTABLE',
        'DATETIMETZ_MUTABLE',
        'DATETIMETZ_IMMUTABLE',
        'DECIMAL',
        'FLOAT',
        'GUID',
        'INTEGER',
        'JSON',
        'JSON_ARRAY',
        'OBJECT',
        'SIMPLE_ARRAY',
        'SMALLINT',
        'STRING',
        'TEXT',
        'TIME_MUTABLE',
        'TIME_IMMUTABLE',
    ];

    /**
     * Checks if exists support for a type.
     */
    public static function hasType(string $name): bool
    {
        return in_array(strtoupper($name), self::DOCTRINE_TYPES);
    }
}
