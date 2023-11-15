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
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
class ODMDefaultPropertiesGuesser extends AbstractDoctrineDefaultPropertiesGuesser
{
    public function __invoke(SymfonyStyle $io, MakeFactoryData $makeFactoryData, MakeFactoryQuery $makeFactoryQuery): void
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

            /** @phpstan-ignore-next-line */
            $isMultiple = ODMClassMetadata::MANY === $item['type'];
            if ($isMultiple) {
                continue;
            }

            $fieldName = $item['fieldName'];
            $isNullable = $makeFactoryData->getObject()->getProperty($fieldName)->getType()?->allowsNull() ?? true;

            if (!$makeFactoryQuery->isAllFields() && $isNullable) {
                continue;
            }

            $this->addDefaultValueUsingFactory($io, $makeFactoryData, $makeFactoryQuery, $fieldName, $item['targetDocument']);
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
