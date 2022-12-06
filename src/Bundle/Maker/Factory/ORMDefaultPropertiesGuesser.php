<?php

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

use Doctrine\ORM\Mapping\ClassMetadataInfo as ORMClassMetadata;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
class ORMDefaultPropertiesGuesser extends AbstractDoctrineDefaultPropertiesGuesser
{
    public function __invoke(SymfonyStyle $io, MakeFactoryData $makeFactoryData, MakeFactoryQuery $makeFactoryQuery): void
    {
        $metadata = $this->getClassMetadata($makeFactoryData);

        if (!$metadata instanceof ORMClassMetadata) {
            throw new \InvalidArgumentException("\"{$makeFactoryData->getObjectFullyQualifiedClassName()}\" is not a valid ORM class.");
        }

        $this->guessDefaultValueForORMAssociativeFields($io, $makeFactoryData, $makeFactoryQuery, $metadata);
        $this->guessDefaultValueForEmbedded($io, $makeFactoryData, $makeFactoryQuery, $metadata);
    }

    public function supports(MakeFactoryData $makeFactoryData): bool
    {
        try {
            $metadata = $this->getClassMetadata($makeFactoryData);

            return $metadata instanceof ORMClassMetadata;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    private function guessDefaultValueForORMAssociativeFields(SymfonyStyle $io, MakeFactoryData $makeFactoryData, MakeFactoryQuery $makeFactoryQuery, ORMClassMetadata $metadata): void
    {
        foreach ($metadata->associationMappings as $item) {
            // if joinColumns is not written entity is default nullable ($nullable = true;)
            if (true === ($item['joinColumns'][0]['nullable'] ?? true)) {
                continue;
            }

            if (isset($item['mappedBy']) || isset($item['joinTable'])) {
                // we don't want to add defaults for X-To-Many relationships
                continue;
            }

            $this->addDefaultValueUsingFactory($io, $makeFactoryData, $makeFactoryQuery, $item['fieldName'], $item['targetEntity']);
        }
    }

    private function guessDefaultValueForEmbedded(SymfonyStyle $io, MakeFactoryData $makeFactoryData, MakeFactoryQuery $makeFactoryQuery, ORMClassMetadata $metadata): void
    {
        foreach ($metadata->embeddedClasses as $fieldName => $item) {
            $isNullable = $makeFactoryData->getObject()->getProperty($fieldName)->getType()?->allowsNull() ?? true;

            if (!$makeFactoryQuery->isAllFields() && $isNullable) {
                continue;
            }

            $this->addDefaultValueUsingFactory($io, $makeFactoryData, $makeFactoryQuery, $fieldName, $item['class']);
        }
    }
}
