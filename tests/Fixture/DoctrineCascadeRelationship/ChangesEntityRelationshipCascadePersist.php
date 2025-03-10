<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * Hack into PHPUnit data provider mechanism to change the cascade persist behavior of Doctrine relationship:
 * - each test declares which relationship it uses, thanks to UsingRelationships attribute
 * - the data provider provides all possible combinations of relationships to change the cascade persist behavior
 * - the listener {@see ChangeCascadePersistOnLoadClassMetadataListener} is used to change the cascade persist behavior
 *   before each test, thanks to "onLoadMetadata" doctrine event.
 *
 * This way, we can test all possible combinations of "cascade persist" on doctrine relationships.
 */
trait ChangesEntityRelationshipCascadePersist
{
    use RequiresORM;

    private static string $methodName = '';

    #[Before]
    public function setUpCascadePersistMetadata(): void
    {
        if (!$this instanceof KernelTestCase) {
            throw new \LogicException('Cannot use trait "ChangesEntityRelationshipCascadePersist" without KernelTestCase.');
        }

        $testMethod = new \ReflectionMethod(static::class, $this->name());
        $usingRelationshipsAttributes = $testMethod->getAttributes(UsingRelationships::class);

        if (!$usingRelationshipsAttributes) {
            return;
        }

        $usingRelationshipsAttributes = $testMethod->getAttributes(DataProvider::class);
        if (1 !== \count($usingRelationshipsAttributes) || 'provideCascadeRelationshipsCombinations' !== $usingRelationshipsAttributes[0]->newInstance()->methodName()) {
            throw new \LogicException(\sprintf('When using attribute "%s", you must use "provideCascadeRelationshipsCombinations" as unique a data provider.', UsingRelationships::class));
        }

        /** @var ChangeCascadePersistOnLoadClassMetadataListener $changeCascadePersistListener */
        $changeCascadePersistListener = self::getContainer()->get(ChangeCascadePersistOnLoadClassMetadataListener::class);
        $changeCascadePersistListener->withMetadata(\array_values($this->providedData()));

        /** @var CacheItemPoolInterface $doctrineMetadataCache */
        $doctrineMetadataCache = self::getContainer()->get('doctrine.orm.default_metadata_cache');
        $doctrineMetadataCache->clear();
    }

    /**
     * @return iterable<list<DoctrineCascadeRelationshipMetadata>>
     */
    public static function provideCascadeRelationshipsCombinations(): iterable
    {
        if (!\getenv('DATABASE_URL')) {
            // this test requires the ORM, but trait RequiresORM is analysed after data provider are called
            // then we need to return at least one empty array to avoid an error
            // in PHPUnit 12, we will be able to use #[RequiresEnvironmentVariable('DATABASE_URL')] to prevent this
            yield ['']; // @phpstan-ignore generator.valueType

            return;
        }

        /**
         * self::$methodName is set in a PHPUnit extension, it's the only way to get the current method name.
         * @see PhpUnitTestExtension
         */
        $attributes = (new \ReflectionMethod(static::class, self::$methodName))->getAttributes(UsingRelationships::class);

        $relationshipsToChange = [];
        foreach ($attributes as $attribute) {
            /** @var UsingRelationships $attributeInstance */
            $attributeInstance = $attribute->newInstance();
            $relationshipsToChange[$attributeInstance->class] = $attributeInstance->relationShips;
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

                $relationshipFields[] = ['class' => $association['sourceEntity'], 'field' => $association['fieldName'], 'isOneToMany' => ClassMetadata::ONE_TO_MANY === $association['type']];
                if ($association['inversedBy'] ?? $association['mappedBy'] ?? null) {
                    /** @var ClassMetadata<object> $metadataTargetEntity */
                    $metadataTargetEntity = $persistenceManager->metadataFor($association['targetEntity']); // @phpstan-ignore argument.templateType
                    $associationTargetEntity = $metadataTargetEntity->getAssociationMapping($association['inversedBy'] ?? $association['mappedBy']);
                    $relationshipFields[] = ['class' => $associationTargetEntity['sourceEntity'], 'field' => $associationTargetEntity['fieldName'], 'isOneToMany' => ClassMetadata::ONE_TO_MANY === $associationTargetEntity['type']];
                }
            }
        }

        yield from DoctrineCascadeRelationshipMetadata::allCombinations($relationshipFields);

        Configuration::shutdown();
    }

    public static function setCurrentProvidedMethodName(string $methodName): void
    {
        self::$methodName = $methodName;
    }
}
