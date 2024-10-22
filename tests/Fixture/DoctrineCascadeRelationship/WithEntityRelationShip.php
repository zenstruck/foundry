<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use PHPUnit\Framework\Attributes\Before;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
trait WithEntityRelationShip
{
    use RequiresORM;

    /**
     * @before
     */
    #[Before]
    public function setUpCascadePersistMetadata(): void
    {
        if (!$this instanceof KernelTestCase) {
            throw new \LogicException('Cannot use trait "WithEntityRelationShip" without KernelTestCase.');
        }

        $attributes = (new \ReflectionMethod(static::class, $this->getName(withDataSet: false)))->getAttributes(UsingRelationShips::class);

        if (!$attributes) {
            return;
        }

        if ($this->getProvidedData()) {
            /** @var ChangeCascadePersistOnLoadClassMetadataListener $changeCascadePersistListener */
            $changeCascadePersistListener = self::getContainer()->get(ChangeCascadePersistOnLoadClassMetadataListener::class);
            $changeCascadePersistListener->withMetadata($this->getProvidedData());
        } else {
            throw new \LogicException(sprintf('When using attribute "%s", you must use "provideCascadeRelationshipsCombination" as a data provider.', UsingRelationShips::class));
        }
    }

    /**
     * @return iterable<list<DoctrineCascadeRelationshipMetadata>>
     */
    private static function provideCascadeRelationshipsCombination(string $methodName): iterable
    {
        $attributes = (new \ReflectionMethod(static::class, $methodName))->getAttributes(UsingRelationShips::class);

        $relationshipsToChange = [];
        foreach ($attributes as $attribute) {
            /** @var UsingRelationShips $attributeInstance */
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
                    throw new \LogicException(sprintf("Wrong parameters for attribute \"%s\". Association \"$class::\$$field\" does not exist.", UsingRelationShips::class));
                }

                $relationshipFields[] = ['class' => $association->sourceEntity, 'field' => $association->fieldName];
            }
        }

        yield from self::generateAllCombinations($relationshipFields);
    }

    /**
     * @param list<array{class: class-string, field: string}> $permutations
     * @return list<list<DoctrineCascadeRelationshipMetadata>>
     */
    private static function generateAllCombinations(array $permutations): array
    {
        $result = [];
        $total = pow(2, count($permutations));

        for ($i = 0; $i < $total; $i++) {
            $temp = [];
            for ($j = 0; $j < count($permutations); $j++) {
                $temp[$j] = new DoctrineCascadeRelationshipMetadata(
                    class: $permutations[$j]['class'],
                    field: $permutations[$j]['field'],
                    cascade: (bool) (($i >> $j) & 1)
                );
            }
            $result[] = $temp;
        }

        return $result;
    }
}
