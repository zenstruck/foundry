<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\PersistenceManager;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * Hack into PHPUnit data provider mechanism to change the cascade persist behavior of Doctrine relationship:
 * - each test declares which relationship it uses, thanks to UsingRelationships attribute
 * - {@see UsingRelationshipsParser} turns this attribute into a data provider, which provides all possible combinations
 *   of cascade persist for these relationships, crossed with the data sets of the test's own data providers
 * - the listener {@see ChangeCascadePersistOnLoadClassMetadataListener} is used to change the cascade persist behavior
 *   before each test, thanks to "onLoadMetadata" doctrine event.
 *
 * This way, we can test all possible combinations of "cascade persist" on doctrine relationships.
 *
 * @phpstan-require-extends KernelTestCase
 */
trait ChangesEntityRelationshipCascadePersist
{
    /** @var array<class-string, array<string, array<array-key, list<DoctrineCascadeRelationshipMetadata>>>> */
    private static array $combinations = [];

    #[BeforeClass]
    public static function _requiresORM(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('SQL database not available.');
        }
    }

    #[Before]
    public function setUpCascadePersistMetadata(): void
    {
        if (!$this instanceof KernelTestCase) {
            throw new \LogicException('Cannot use trait "ChangesEntityRelationshipCascadePersist" without KernelTestCase.');
        }

        if (!$combination = $this->cascadePersistCombination()) {
            return;
        }

        /** @var ChangeCascadePersistOnLoadClassMetadataListener $changeCascadePersistListener */
        $changeCascadePersistListener = self::getContainer()->get(ChangeCascadePersistOnLoadClassMetadataListener::class);
        $changeCascadePersistListener->withMetadata($combination);

        self::clearDoctrineMetadataCache();
    }

    // DAMA's metadata cache is static: without this, the next tests would inherit the changed mapping.
    // Priority 1 makes it run before tearDown() shuts the kernel down.
    #[After(1)]
    public function restoreCascadePersistMetadata(): void
    {
        self::clearDoctrineMetadataCache();
    }

    /**
     * Called by {@see UsingRelationshipsParser} for each test using #[UsingRelationships].
     *
     * @param  array<array-key, array<mixed>>    $dataSets data sets of the test's own data providers
     * @return iterable<array-key, array<mixed>>
     */
    public static function cascadeRelationshipsCombinations(string $methodName, array $dataSets): iterable
    {
        $dataSets = $dataSets ?: ['' => []];

        if (!\getenv('DATABASE_URL')) {
            // data providers are resolved before #[RequiresEnvironmentVariable] is checked
            yield from $dataSets;

            return;
        }

        $attributes = (new \ReflectionMethod(static::class, $methodName))->getAttributes(UsingRelationships::class);

        $relationshipsToChange = [];
        foreach ($attributes as $attribute) {
            /** @var UsingRelationships $attributeInstance */
            $attributeInstance = $attribute->newInstance();
            $relationshipsToChange[$attributeInstance->class] = $attributeInstance->relationships;
        }

        /** @var PersistenceManager $persistenceManager */
        $persistenceManager = self::getContainer()->get(PersistenceManager::class);

        $relationshipFields = [];
        foreach ($relationshipsToChange as $class => $fields) {
            $metadata = $persistenceManager->metadataFor($class);

            if (!$metadata instanceof ClassMetadata || $metadata->isEmbeddedClass) {
                throw new \InvalidArgumentException("{$class} is not an entity using ORM");
            }

            foreach ($fields as $field) {
                try {
                    $association = $metadata->getAssociationMapping($field);
                } catch (MappingException) {
                    throw new \LogicException(\sprintf("Wrong parameters for attribute \"%s\". Association \"{$class}::\${$field}\" does not exist.", UsingRelationships::class));
                }

                $relationshipFields["{$association['sourceEntity']}::{$association['fieldName']}"] = ['class' => $association['sourceEntity'], 'field' => $association['fieldName'], 'isOneToMany' => ClassMetadata::ONE_TO_MANY === $association['type']];
                if ($association['inversedBy'] ?? $association['mappedBy'] ?? null) {
                    /** @var ClassMetadata<object> $metadataTargetEntity */
                    $metadataTargetEntity = $persistenceManager->metadataFor($association['targetEntity']); // @phpstan-ignore argument.templateType
                    $associationTargetEntity = $metadataTargetEntity->getAssociationMapping($association['inversedBy'] ?? $association['mappedBy']);
                    $relationshipFields["{$associationTargetEntity['sourceEntity']}::{$associationTargetEntity['fieldName']}"] = ['class' => $associationTargetEntity['sourceEntity'], 'field' => $associationTargetEntity['fieldName'], 'isOneToMany' => ClassMetadata::ONE_TO_MANY === $associationTargetEntity['type']];
                }
            }
        }

        foreach (DoctrineCascadeRelationshipMetadata::allCombinations(\array_values($relationshipFields)) as $combinationName => $combination) {
            foreach ($dataSets as $dataSetName => $arguments) {
                $name = '' === $dataSetName ? $combinationName : "{$combinationName} / {$dataSetName}";
                self::$combinations[static::class][$methodName][$name] = $combination;

                // PHPUnit only shows the name of a data set that has arguments
                yield $name => $arguments ?: $combination;
            }
        }

        Configuration::shutdown();
        static::ensureKernelShutdown();
    }

    /**
     * @return list<DoctrineCascadeRelationshipMetadata>
     */
    private function cascadePersistCombination(): array
    {
        return self::$combinations[static::class][$this->name()][$this->dataName()] ?? []; // @phpstan-ignore method.internal, method.internal
    }

    private static function clearDoctrineMetadataCache(): void
    {
        /** @var CacheItemPoolInterface $doctrineMetadataCache */
        $doctrineMetadataCache = self::getContainer()->get('doctrine.orm.default_metadata_cache');
        $doctrineMetadataCache->clear();
    }
}
