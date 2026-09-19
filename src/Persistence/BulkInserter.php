<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Database-agnostic bulk inserter.
 *
 * Uses PostgreSQL COPY FROM STDIN when the driver supports it,
 * falls back to multi-row INSERT for all other DBMS.
 */
final class BulkInserter
{
    private const COPY_NULL = '__FOUNDRY_NULL__';

    /**
     * Bulk-insert a set of entities and their owned dependencies.
     *
     * @param list<object> $objects Root entities to persist
     */
    public static function bulkInsertObjectGraph(EntityManagerInterface $em, array $objects): void
    {
        if ([] === $objects) {
            return;
        }

        if (self::canSkipGraphWalk($em, $objects)) {
            $class = $objects[0]::class;
            self::insertEntities($em, $objects);
            self::assignSequentialIds($em, $class, $objects);

            return;
        }

        $entityGroups = self::collectEntityGroups($em, $objects);
        $sorted = self::topologicalSort($em, $entityGroups);

        foreach ($sorted as $class => $entities) {
            self::insertEntities($em, $entities);
            self::assignSequentialIds($em, $class, $entities);
        }
    }

    /** @var array<class-string, array{fields: list<array{col: string, propChain: list<\ReflectionProperty>, type: string}>, assocs: list<array{col: string, prop: \ReflectionProperty, refCol: string, targetIdProp: \ReflectionProperty}>, types: array<string, string>}> */
    private static array $rowExtractorCache = [];

    private static function getRowExtractor(EntityManagerInterface $em, string $class): array
    {
        if (isset(self::$rowExtractorCache[$class])) {
            return self::$rowExtractorCache[$class];
        }

        /** @var ClassMetadata<object> $metadata */
        $metadata = $em->getClassMetadata($class);
        $fields = [];
        $assocs = [];
        $types = [];

        foreach ($metadata->fieldMappings as $fieldName => $mapping) {
            if ($mapping->id) {
                continue;
            }

            $propChain = [];

            if (\str_contains($fieldName, '.')) {
                $parts = \explode('.', $fieldName);

                foreach ($parts as $i => $part) {
                    $ownerClass = 0 === $i ? $class : $metadata->embeddedClasses[$parts[0]]->class;

                    if ($i > 0) {
                        $ownerClass = $metadata->embeddedClasses[\implode('.', \array_slice($parts, 0, $i))]->class ?? $ownerClass;
                    }

                    $propChain[] = new \ReflectionProperty($ownerClass, $part);
                }
            } else {
                $propChain[] = new \ReflectionProperty($class, $fieldName);
            }

            $fields[] = [
                'col' => $mapping->columnName,
                'propChain' => $propChain,
                'type' => $mapping->type,
            ];
            $types[$mapping->columnName] = $mapping->type;
        }

        foreach ($metadata->associationMappings as $fieldName => $assoc) {
            if (!$assoc->isOwningSide() || !isset($assoc->joinColumns)) {
                continue;
            }

            $targetMetadata = $em->getClassMetadata($assoc->targetEntity);
            $targetIdField = $targetMetadata->getSingleIdentifierFieldName();

            foreach ($assoc->joinColumns as $joinColumn) {
                $assocs[] = [
                    'col' => $joinColumn->name,
                    'prop' => new \ReflectionProperty($class, $fieldName),
                    'refCol' => $joinColumn->referencedColumnName,
                    'targetIdProp' => new \ReflectionProperty($assoc->targetEntity, $targetIdField),
                ];
                $types[$joinColumn->name] = 'integer';
            }
        }

        return self::$rowExtractorCache[$class] = ['fields' => $fields, 'assocs' => $assocs, 'types' => $types];
    }

    /** @var array<class-string, array{fieldMap: array<string, string>, assocMap: array<string, array{col: string, idProp: \ReflectionProperty}>, types: array<string, string>}|null> */
    private static array $attrMapCache = [];

    /**
     * Build a mapping from factory attribute names to column names/types.
     * Returns null if the entity has embeddables or complex mappings.
     *
     * @return array{fieldMap: array<string, string>, assocMap: array<string, array{col: string, idProp: \ReflectionProperty}>, types: array<string, string>}|null
     */
    public static function getAttributeColumnMap(EntityManagerInterface $em, string $class): ?array
    {
        if (\array_key_exists($class, self::$attrMapCache)) {
            return self::$attrMapCache[$class];
        }

        /** @var ClassMetadata<object> $metadata */
        $metadata = $em->getClassMetadata($class);

        if ([] !== $metadata->embeddedClasses) {
            return self::$attrMapCache[$class] = null;
        }

        $fieldMap = [];
        $assocMap = [];
        $types = [];

        foreach ($metadata->fieldMappings as $fieldName => $mapping) {
            if ($mapping->id) {
                continue;
            }

            $fieldMap[$fieldName] = $mapping->columnName;
            $types[$mapping->columnName] = $mapping->type;
        }

        foreach ($metadata->associationMappings as $fieldName => $assoc) {
            if (!$assoc->isOwningSide() || !isset($assoc->joinColumns)) {
                continue;
            }

            $targetMetadata = $em->getClassMetadata($assoc->targetEntity);
            $targetIdField = $targetMetadata->getSingleIdentifierFieldName();

            foreach ($assoc->joinColumns as $joinColumn) {
                $assocMap[$fieldName] = [
                    'col' => $joinColumn->name,
                    'idProp' => new \ReflectionProperty($assoc->targetEntity, $targetIdField),
                ];
                $types[$joinColumn->name] = 'integer';
            }
        }

        return self::$attrMapCache[$class] = ['fieldMap' => $fieldMap, 'assocMap' => $assocMap, 'types' => $types];
    }

    /**
     * Convert a factory attributes array directly to a DB row.
     *
     * @param array<string, mixed> $attributes
     * @param array{fieldMap: array<string, string>, assocMap: array<string, array{col: string, idProp: \ReflectionProperty}>, types: array<string, string>} $map
     *
     * @return array<string, mixed>
     */
    public static function attributesToRow(array $attributes, array $map): array
    {
        $row = [];

        foreach ($map['fieldMap'] as $attrName => $colName) {
            if (\array_key_exists($attrName, $attributes)) {
                $row[$colName] = $attributes[$attrName];
            }
        }

        foreach ($map['assocMap'] as $attrName => $assocInfo) {
            if (\array_key_exists($attrName, $attributes) && null !== $attributes[$attrName]) {
                $row[$assocInfo['col']] = $assocInfo['idProp']->getValue($attributes[$attrName]);
            } else {
                $row[$assocInfo['col']] = null;
            }
        }

        return $row;
    }

    /**
     * @return array{values: array<string, mixed>, types: array<string, string>}
     */
    public static function extractRow(EntityManagerInterface $em, object $entity): array
    {
        $extractor = self::getRowExtractor($em, $entity::class);
        $values = [];

        foreach ($extractor['fields'] as $field) {
            $value = $entity;

            foreach ($field['propChain'] as $prop) {
                $value = $prop->getValue($value);

                if (null === $value) {
                    break;
                }
            }

            $values[$field['col']] = $value;
        }

        foreach ($extractor['assocs'] as $assoc) {
            $related = $assoc['prop']->getValue($entity);
            $values[$assoc['col']] = null !== $related ? $assoc['targetIdProp']->getValue($related) : null;
        }

        return ['values' => $values, 'types' => $extractor['types']];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param array<string, string>      $types
     */
    public static function insertBatch(Connection $connection, string $tableName, array $rows, array $types, int $batchSize = 1000): int
    {
        if ([] === $rows) {
            return 0;
        }

        if (self::supportsCopy($connection)) {
            return self::insertViaCopy($connection, $tableName, $rows);
        }

        return self::insertViaMultiRow($connection, $tableName, $rows, $types, $batchSize);
    }

    /**
     * @param list<object> $entities
     */
    public static function insertEntities(EntityManagerInterface $em, array $entities, int $batchSize = 1000): int
    {
        if ([] === $entities) {
            return 0;
        }

        $connection = $em->getConnection();
        /** @var ClassMetadata<object> $metadata */
        $metadata = $em->getClassMetadata($entities[0]::class);
        $tableName = $metadata->getTableName();

        $rows = [];
        $types = null;

        foreach ($entities as $entity) {
            $extracted = self::extractRow($em, $entity);
            $rows[] = $extracted['values'];
            $types ??= $extracted['types'];
        }

        return self::insertBatch($connection, $tableName, $rows, $types, $batchSize);
    }

    private static function supportsCopy(Connection $connection): bool
    {
        try {
            $native = $connection->getNativeConnection();

            return $native instanceof \PDO
                && 'pgsql' === $native->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private static function insertViaCopy(Connection $connection, string $tableName, array $rows): int
    {
        /** @var \PDO $pdo */
        $pdo = $connection->getNativeConnection();
        $columns = \array_keys($rows[0]);
        $colCount = \count($columns);

        $quotedColumns = \array_map(
            static fn (string $c) => '"'.\str_replace('"', '""', $c).'"',
            $columns,
        );
        $fieldsList = \implode(', ', $quotedColumns);
        $quotedTable = '"'.\str_replace('"', '""', $tableName).'"';

        $lines = [];

        foreach ($rows as $row) {
            $line = self::toCopyText($row[$columns[0]]);
            for ($i = 1; $i < $colCount; ++$i) {
                $line .= "\t" . self::toCopyText($row[$columns[$i]]);
            }
            $lines[] = $line;
        }

        $pdo->pgsqlCopyFromArray($quotedTable, $lines, "\t", self::COPY_NULL, $fieldsList);

        return \count($rows);
    }

    private static function toCopyText(mixed $value): string
    {
        if (null === $value) {
            return self::COPY_NULL;
        }

        if (\is_string($value)) {
            return self::escapeCopy($value);
        }

        if (\is_int($value) || \is_float($value)) {
            return (string) $value;
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value instanceof \BackedEnum) {
            return self::escapeCopy((string) $value->value);
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s.u');
        }

        if (\is_array($value)) {
            return self::escapeCopy(\json_encode($value, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE));
        }

        if ($value instanceof \UnitEnum) {
            return self::escapeCopy($value->name);
        }

        if ($value instanceof \Symfony\Component\Uid\AbstractUid) {
            return $value->toRfc4122();
        }

        return self::escapeCopy((string) $value);
    }

    private static function escapeCopy(string $value): string
    {
        return \str_replace(
            ["\\", "\t", "\n", "\r"],
            ["\\\\", "\\t", "\\n", "\\r"],
            $value,
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param array<string, string>      $types
     */
    private static function insertViaMultiRow(Connection $connection, string $tableName, array $rows, array $types, int $batchSize): int
    {
        $platform = $connection->getDatabasePlatform();
        $columns = \array_keys($rows[0]);
        $quotedTable = $platform->quoteIdentifier($tableName);
        $quotedColumns = \array_map($platform->quoteIdentifier(...), $columns);
        $columnsSql = \implode(', ', $quotedColumns);
        $rowPlaceholder = '('.\implode(', ', \array_fill(0, \count($columns), '?')).')';

        $total = 0;

        foreach (\array_chunk($rows, $batchSize) as $chunk) {
            $sql = \sprintf(
                'INSERT INTO %s (%s) VALUES %s',
                $quotedTable,
                $columnsSql,
                \implode(', ', \array_fill(0, \count($chunk), $rowPlaceholder)),
            );

            $flatValues = [];
            $flatTypes = [];

            foreach ($chunk as $row) {
                foreach ($columns as $col) {
                    $flatValues[] = $row[$col] ?? null;
                    $flatTypes[] = $types[$col] ?? 'string';
                }
            }

            $total += $connection->executeStatement($sql, $flatValues, $flatTypes);
        }

        return $total;
    }

    /**
     * Check if we can skip the full graph walk (collectEntityGroups + topologicalSort).
     *
     * True when all objects are the same class and all their FK targets already have IDs.
     * Checks only the first object — the batch is homogeneous by construction.
     *
     * @param list<object> $objects
     */
    private static function canSkipGraphWalk(EntityManagerInterface $em, array $objects): bool
    {
        $info = self::getEntityInfo($em, $objects[0]::class);

        if (null === $info) {
            return false;
        }

        foreach ($info['assocs'] as $assoc) {
            $related = $assoc['prop']->getValue($objects[0]);

            if (null === $related) {
                continue;
            }

            $targetInfo = self::getEntityInfo($em, $related::class);

            if (null === $targetInfo || null === $targetInfo['idProp']->getValue($related)) {
                return false;
            }
        }

        return true;
    }

    /** @var array<class-string, array{idProp: \ReflectionProperty, assocs: list<array{prop: \ReflectionProperty, target: class-string}>}|null> */
    private static array $entityInfoCache = [];

    /**
     * @param list<object> $rootObjects
     *
     * @return array<class-string, list<object>>
     */
    private static function collectEntityGroups(EntityManagerInterface $em, array $rootObjects): array
    {
        $groups = [];
        $visited = [];
        $stack = $rootObjects;

        while ([] !== $stack) {
            $entity = \array_pop($stack);
            $oid = \spl_object_id($entity);

            if (isset($visited[$oid])) {
                continue;
            }

            $visited[$oid] = true;

            $info = self::getEntityInfo($em, $entity::class);

            if (null === $info) {
                continue;
            }

            if (null !== $info['idProp']->getValue($entity)) {
                continue;
            }

            $groups[$entity::class][] = $entity;

            foreach ($info['assocs'] as $assoc) {
                $related = $assoc['prop']->getValue($entity);

                if (null !== $related && \is_object($related)) {
                    $stack[] = $related;
                }
            }
        }

        return $groups;
    }

    private static function getEntityInfo(EntityManagerInterface $em, string $class): ?array
    {
        if (\array_key_exists($class, self::$entityInfoCache)) {
            return self::$entityInfoCache[$class];
        }

        try {
            $metadata = $em->getClassMetadata($class);
        } catch (\Throwable) {
            return self::$entityInfoCache[$class] = null;
        }

        $idField = $metadata->getSingleIdentifierFieldName();
        $assocs = [];

        foreach ($metadata->associationMappings as $fieldName => $assoc) {
            if ($assoc->isOwningSide()) {
                $assocs[] = [
                    'prop' => new \ReflectionProperty($class, $fieldName),
                    'target' => $assoc->targetEntity,
                ];
            }
        }

        return self::$entityInfoCache[$class] = [
            'idProp' => new \ReflectionProperty($class, $idField),
            'assocs' => $assocs,
        ];
    }


    /**
     * @param array<class-string, list<object>> $entityGroups
     *
     * @return array<class-string, list<object>>
     */
    private static function topologicalSort(EntityManagerInterface $em, array $entityGroups): array
    {
        $deps = [];

        foreach (\array_keys($entityGroups) as $class) {
            $deps[$class] = [];
            /** @var ClassMetadata<object> $metadata */
            $metadata = $em->getClassMetadata($class);

            foreach ($metadata->associationMappings as $assoc) {
                if ($assoc->isOwningSide() && isset($entityGroups[$assoc->targetEntity])) {
                    $deps[$class][] = $assoc->targetEntity;
                }
            }
        }

        $sorted = [];

        while ([] !== $deps) {
            $noDep = null;

            foreach ($deps as $class => $d) {
                if ([] === $d) {
                    $noDep = $class;
                    break;
                }
            }

            if (null === $noDep) {
                throw new \LogicException('Circular FK dependency between: '.\implode(', ', \array_keys($deps)));
            }

            $sorted[$noDep] = $entityGroups[$noDep];
            unset($deps[$noDep]);

            foreach ($deps as &$d) {
                $d = \array_values(\array_diff($d, [$noDep]));
            }
        }

        return $sorted;
    }

    /**
     * @param class-string $class
     * @param list<object> $entities
     */
    private static function assignSequentialIds(EntityManagerInterface $em, string $class, array $entities): void
    {
        if ([] === $entities) {
            return;
        }

        /** @var ClassMetadata<object> $metadata */
        $metadata = $em->getClassMetadata($class);

        if (ClassMetadata::GENERATOR_TYPE_IDENTITY !== $metadata->generatorType) {
            return;
        }

        $idField = $metadata->getSingleIdentifierFieldName();
        $columnName = $metadata->getColumnName($idField);
        $connection = $em->getConnection();
        $platform = $connection->getDatabasePlatform();
        $tableName = $platform->quoteIdentifier($metadata->getTableName());
        $quotedColumn = $platform->quoteIdentifier($columnName);

        $maxId = (int) $connection->fetchOne("SELECT COALESCE(MAX({$quotedColumn}), 0) FROM {$tableName}");
        $firstId = $maxId - \count($entities) + 1;

        $reflProp = new \ReflectionProperty($class, $idField);

        foreach ($entities as $i => $entity) {
            $reflProp->setValue($entity, $firstId + $i);
        }
    }
}
