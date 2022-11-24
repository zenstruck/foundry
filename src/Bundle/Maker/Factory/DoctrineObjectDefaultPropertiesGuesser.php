<?php

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ODMClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo as ORMClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * @internal
 */
class DoctrineObjectDefaultPropertiesGuesser implements DefaultPropertiesGuesser
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
        'DATETIME_MUTABLE' => 'self::faker()->dateTime(),',
        'DATETIME_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),',
        'DATETIMETZ_MUTABLE' => 'self::faker()->dateTime(),',
        'DATETIMETZ_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->dateTime()),',
        'DECIMAL' => 'self::faker()->randomFloat(),',
        'FLOAT' => 'self::faker()->randomFloat(),',
        'INTEGER' => 'self::faker()->randomNumber(),',
        'JSON' => '[],',
        'JSON_ARRAY' => '[],',
        'SIMPLE_ARRAY' => '[],',
        'SMALLINT' => 'self::faker()->numberBetween(1, 32767),',
        'STRING' => 'self::faker()->text({length}),',
        'TEXT' => 'self::faker()->text({length}),',
        'TIME_MUTABLE' => 'self::faker()->datetime(),',
        'TIME_IMMUTABLE' => '\DateTimeImmutable::createFromMutable(self::faker()->datetime()),',
    ];

    public function __construct(private ManagerRegistry $managerRegistry, private FactoryFinder $factoryFinder)
    {
    }

    public function __invoke(MakeFactoryData $makeFactoryData, bool $allFields): void
    {
        $class = $makeFactoryData->getObjectFullyQualifiedClassName();

        $em = $this->managerRegistry->getManagerForClass($class);

        if (!$em instanceof ObjectManager) {
            return;
        }

        /** @var ORMClassMetadata|ODMClassMetadata $metadata */
        $metadata = $em->getClassMetadata($class);

        $dbType = $em instanceof EntityManagerInterface ? 'ORM' : 'ODM';

        $this->guessDefaultValueForAssociativeFields($makeFactoryData, $metadata, $dbType, $allFields);
        $this->guessDefaultValueForScalarFields($makeFactoryData, $metadata, $dbType, $allFields);
    }

    public function supports(bool $persisted): bool
    {
        return true === $persisted;
    }

    private function guessDefaultValueForAssociativeFields(MakeFactoryData $makeFactoryData, ODMClassMetadata|ORMClassMetadata $metadata, string $dbType, bool $allFields): void
    {
        foreach ($metadata->associationMappings as $item) {
            // if joinColumns is not written entity is default nullable ($nullable = true;)
            if (!\array_key_exists('joinColumns', $item)) {
                continue;
            }

            if (!\array_key_exists('nullable', $item['joinColumns'][0] ?? [])) {
                continue;
            }

            if (true === $item['joinColumns'][0]['nullable']) {
                continue;
            }

            $fieldName = $item['fieldName'];

            if (!$factoryClass = $this->factoryFinder->getFactoryForClass($item['targetEntity'])) {
                $makeFactoryData->addDefaultProperty(\lcfirst($fieldName), "null, // TODO add {$item['targetEntity']} {$dbType} type manually");

                continue;
            }

            $factory = new \ReflectionClass($factoryClass);
            $makeFactoryData->addUse($factory->getName());
            $makeFactoryData->addDefaultProperty(\lcfirst($fieldName), "{$factory->getShortName()}::new(),");
        }
    }

    private function guessDefaultValueForScalarFields(MakeFactoryData $makeFactoryData, ODMClassMetadata|ORMClassMetadata $metadata, string $dbType, bool $allFields): void
    {
        $ids = $metadata->getIdentifierFieldNames();

        foreach ($metadata->fieldMappings as $property) {
            // ignore identifiers and nullable fields
            if ((!$allFields && ($property['nullable'] ?? false)) || \in_array($property['fieldName'], $ids, true)) {
                continue;
            }

            $type = \mb_strtoupper($property['type']);
            $value = "null, // TODO add {$type} {$dbType} type manually";
            $length = $property['length'] ?? '';

            if (\array_key_exists($type, self::DEFAULTS)) {
                $value = self::DEFAULTS[$type];
            }

            $makeFactoryData->addDefaultProperty($property['fieldName'], \str_replace('{length}', (string) $length, $value));
        }
    }
}
