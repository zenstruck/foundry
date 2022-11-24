<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;

/** @internal  */
abstract class AbstractDoctrineDefaultPropertiesGuesser implements DefaultPropertiesGuesser
{
    public function __construct(protected ManagerRegistry $managerRegistry, private FactoryFinder $factoryFinder)
    {
    }

    /** @param class-string $fieldClass */
    protected function addDefaultValueUsingFactory(MakeFactoryData $makeFactoryData, string $fieldName, string $fieldClass, bool $isMultiple = false): void
    {
        if (!$factoryClass = $this->factoryFinder->getFactoryForClass($fieldClass)) {
            $makeFactoryData->addDefaultProperty(\lcfirst($fieldName), "null, // TODO add {$fieldClass} type manually");

            return;
        }

        $factoryMethod = $isMultiple ? 'new()->many(5)' : 'new()';

        $factory = new \ReflectionClass($factoryClass);
        $makeFactoryData->addUse($factory->getName());
        $makeFactoryData->addDefaultProperty(\lcfirst($fieldName), "{$factory->getShortName()}::{$factoryMethod},");
    }

    protected function getClassMetadata(MakeFactoryData $makeFactoryData): ClassMetadata
    {
        $class = $makeFactoryData->getObjectFullyQualifiedClassName();

        $em = $this->managerRegistry->getManagerForClass($class);

        if (!$em instanceof ObjectManager) {
            throw new \InvalidArgumentException("\"{$class}\" is not a valid Doctrine class name.");
        }

        return $em->getClassMetadata($class);
    }
}
