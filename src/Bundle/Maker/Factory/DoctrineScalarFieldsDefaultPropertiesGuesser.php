<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ODMClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo as ORMClassMetadata;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
final class DoctrineScalarFieldsDefaultPropertiesGuesser extends AbstractDoctrineDefaultPropertiesGuesser
{
    private const DEFAULTS = [
        'ARRAY' => '[],',
        'ASCII_STRING' => 'self::faker()->text({length}),',
        'BIGINT' => 'self::faker()->randomNumber(),',
        'BLOB' => 'self::faker()->text(),',
        'BOOLEAN' => 'self::faker()->boolean(),',
        'DATE' => 'self::faker()->dateTime(),',
        'DATE_MUTABLE' => 'self::faker()->dateTime(),',
        'DATE_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),',
        'DATETIME' => 'self::faker()->dateTime(),',
        'DATETIME_MUTABLE' => 'self::faker()->dateTime(),',
        'DATETIME_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),',
        'DATETIMETZ_MUTABLE' => 'self::faker()->dateTime(),',
        'DATETIMETZ_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),',
        'DECIMAL' => 'self::faker()->randomFloat(),',
        'FLOAT' => 'self::faker()->randomFloat(),',
        'INTEGER' => 'self::faker()->randomNumber(),',
        'INT' => 'self::faker()->randomNumber(),',
        'JSON' => '[],',
        'JSON_ARRAY' => '[],',
        'SIMPLE_ARRAY' => '[],',
        'SMALLINT' => 'self::faker()->numberBetween(1, 32767),',
        'STRING' => 'self::faker()->text({length}),',
        'TEXT' => 'self::faker()->text({length}),',
        'TIME_MUTABLE' => 'self::faker()->datetime(),',
        'TIME_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->datetime()),',
    ];

    public function __invoke(SymfonyStyle $io, MakeFactoryData $makeFactoryData, MakeFactoryQuery $makeFactoryQuery): void
    {
        /** @var ODMClassMetadata|ORMClassMetadata $metadata */
        $metadata = $this->getClassMetadata($makeFactoryData);

        $ids = $metadata->getIdentifierFieldNames();

        foreach ($metadata->fieldMappings as $property) {
            if ($property['embedded'] ?? false) {
                // skip ODM embedded
                continue;
            }

            $fieldName = $property['fieldName'];

            if (\str_contains($fieldName, '.')) {
                // this is a "subfield" of an ORM embeddable field.
                continue;
            }

            // ignore identifiers and nullable fields
            if ((!$makeFactoryQuery->isAllFields() && ($property['nullable'] ?? false)) || \in_array($fieldName, $ids, true)) {
                continue;
            }

            $type = \mb_strtoupper($property['type']);
            if (isset($property['enumType'])) {
                $makeFactoryData->addEnumDefaultProperty($fieldName, $property['enumType']);

                continue;
            }

            $value = "null, // TODO add {$type} type manually";
            $length = $property['length'] ?? '';

            if (\array_key_exists($type, self::DEFAULTS)) {
                $value = self::DEFAULTS[$type];
            }

            $makeFactoryData->addDefaultProperty($fieldName, \str_replace('{length}', (string) $length, $value));
        }
    }

    public function supports(MakeFactoryData $makeFactoryData): bool
    {
        return $makeFactoryData->isPersisted();
    }
}
