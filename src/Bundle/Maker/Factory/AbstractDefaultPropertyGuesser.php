<?php

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
abstract class AbstractDefaultPropertyGuesser implements DefaultPropertiesGuesser
{
    public function __construct(private FactoryClassMap $factoryClassMap, private FactoryGenerator $factoryGenerator)
    {
    }

    /** @param class-string $fieldClass */
    protected function addDefaultValueUsingFactory(SymfonyStyle $io, MakeFactoryData $makeFactoryData, MakeFactoryQuery $makeFactoryQuery, string $fieldName, string $fieldClass): void
    {
        if (!$factoryClass = $this->factoryClassMap->getFactoryForClass($fieldClass)) {
            if ($makeFactoryQuery->generateAllFactories() || $io->confirm(
                    "A factory for class \"{$fieldClass}\" is missing for field {$makeFactoryData->getObjectShortName()}::\${$fieldName}. Do you want to create it?"
                )) {
                $factoryClass = $this->factoryGenerator->generateFactory($io, $makeFactoryQuery->withClass($fieldClass));
            } else {
                $makeFactoryData->addDefaultProperty(\lcfirst($fieldName), "null, // TODO add {$fieldClass} type manually");

                return;
            }
        }

        $makeFactoryData->addUse($factoryClass);

        $factoryShortName = Str::getShortClassName($factoryClass);
        $makeFactoryData->addDefaultProperty(\lcfirst($fieldName), "{$factoryShortName}::new(),");
    }
}
