<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\InverseSideMapping;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\OwningSideMapping;
use PHPUnit\Framework\Attributes\Before;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @internal
 */
trait WithEntityRelationship
{
    use RequiresORM;

    private static string $methodName = '';

    /**
     * @before
     */
    #[Before]
    public function setUpCascadePersistMetadata(): void
    {
        if (!$this instanceof KernelTestCase) {
            throw new \LogicException('Cannot use trait "WithEntityRelationship" without KernelTestCase.');
        }

        $testName = method_exists($this, 'getName') ? $this->getName(withDataSet: false) : $this->name(); // @phpstan-ignore method.notFound, argument.unknown
        $attributes = (new \ReflectionMethod(static::class, $testName))->getAttributes(UsingRelationships::class);

        if (!$attributes) {
            return;
        }

        $providedData = method_exists($this, 'getProvidedData') ? $this->getProvidedData() : $this->providedData(); // @phpstan-ignore method.notFound

        if ($providedData) {
            /** @var ChangeCascadePersistOnLoadClassMetadataListener $changeCascadePersistListener */
            $changeCascadePersistListener = self::getContainer()->get(ChangeCascadePersistOnLoadClassMetadataListener::class);
            $changeCascadePersistListener->withMetadata($providedData);

            /** @var CacheItemPoolInterface $doctrineMetadataCache */
            $doctrineMetadataCache = self::getContainer()->get('doctrine.orm.default_metadata_cache');
            $doctrineMetadataCache->clear();
        } else {
            throw new \LogicException(sprintf('When using attribute "%s", you must use "provideCascadeRelationshipsCombination" as a data provider.', UsingRelationships::class));
        }
    }

    /**
     * @return iterable<list<DoctrineCascadeRelationshipMetadata>>
     */
    public static function provideCascadeRelationshipsCombinationV9(string $methodName): iterable
    {
        self::$methodName = $methodName;

        yield from self::provideCascadeRelationshipsCombination();
    }

    /**
     * @return iterable<list<DoctrineCascadeRelationshipMetadata>>
     */
    public static function provideCascadeRelationshipsCombination(): iterable
    {
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
                throw new \InvalidArgumentException("$class is not an entity using ORM");
            }

            foreach ($fields as $field) {
                try {
                    $association = $metadata->getAssociationMapping($field);
                } catch (MappingException) {
                    throw new \LogicException(sprintf("Wrong parameters for attribute \"%s\". Association \"$class::\$$field\" does not exist.", UsingRelationships::class));
                }

                $relationshipFields[] = ['class' => $association->sourceEntity, 'field' => $association->fieldName];
                if ($association instanceof OwningSideMapping && $association->inversedBy !== null) {
                    $relationshipFields[] = ['class' => $association->targetEntity, 'field' => $association->inversedBy];
                }
                if ($association instanceof InverseSideMapping) {
                    $relationshipFields[] = ['class' => $association->targetEntity, 'field' => $association->mappedBy];
                }
            }
        }

        yield from self::generateAllCombinations($relationshipFields);
    }

    public static function setCurrentProvidedMethodName(string $methodName): void
    {
        self::$methodName = $methodName;
    }

    /**
     * @param list<array{class: class-string, field: string}> $permutations
     * @return \Generator<list<DoctrineCascadeRelationshipMetadata>>
     */
    private static function generateAllCombinations(array $permutations): iterable
    {
        $total = pow(2, count($permutations));

        for ($i = 0; $i < $total; $i++) {
            $temp = [];

            $permutationName = "\n";
            for ($j = 0; $j < count($permutations); $j++) {
                $metadata = new DoctrineCascadeRelationshipMetadata(
                    class: $permutations[$j]['class'],
                    field: $permutations[$j]['field'],
                    cascade: (bool)(($i >> $j) & 1)
                );

                $temp[] = $metadata;
                $permutationName = "{$permutationName}$metadata\n";
            }

            yield $permutationName => $temp;
        }
    }
}
