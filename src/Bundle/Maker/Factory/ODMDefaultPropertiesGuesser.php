<?php

namespace Zenstruck\Foundry\Bundle\Maker\Factory;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ODMClassMetadata;

/**
 * @internal
 */
class ODMDefaultPropertiesGuesser extends AbstractDoctrineDefaultPropertiesGuesser
{
    public function __invoke(MakeFactoryData $makeFactoryData, bool $allFields): void
    {
        $metadata = $this->getClassMetadata($makeFactoryData);

        if (!$metadata instanceof ODMClassMetadata) {
            throw new \InvalidArgumentException("\"{$makeFactoryData->getObjectFullyQualifiedClassName()}\" is not a valid ODM class.");
        }

        foreach ($metadata->associationMappings as $item) {
            /** @phpstan-ignore-next-line */
            if (!$item['embedded'] || !$item['targetDocument']) {
                // foundry does not support ODM references
                continue;
            }

            $fieldName = $item['fieldName'];
            /** @phpstan-ignore-next-line */
            $isMultiple = ODMClassMetadata::MANY === $item['type'];

            $isNullable = $makeFactoryData->getObject()->getProperty($fieldName)->getType()?->allowsNull() ?? true;

            if (!$allFields && ($isMultiple || $isNullable)) {
                continue;
            }

            $this->addDefaultValueUsingFactory($makeFactoryData, $fieldName, $item['targetDocument'], $isMultiple);
        }
    }

    public function supports(MakeFactoryData $makeFactoryData): bool
    {
        try {
            $metadata = $this->getClassMetadata($makeFactoryData);

            return $metadata instanceof ODMClassMetadata;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
