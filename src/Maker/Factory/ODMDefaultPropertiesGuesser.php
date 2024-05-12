<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Maker\Factory;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ODMClassMetadata;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zenstruck\Foundry\Persistence\Exception\NoPersistenceStrategy;

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
            // @phpstan-ignore-next-line
            if (!($item['embedded'] ?? false) || !($item['targetDocument'] ?? false)) {
                // foundry does not support ODM references
                continue;
            }

            /** @phpstan-ignore-next-line */
            $isMultiple = ODMClassMetadata::MANY === $item['type'];
            if ($isMultiple) {
                continue;
            }

            $fieldName = $item['fieldName']; // @phpstan-ignore-line

            if (!$makeFactoryQuery->isAllFields() && $item['nullable']) { // @phpstan-ignore-line
                continue;
            }

            $this->addDefaultValueUsingFactory($io, $makeFactoryData, $makeFactoryQuery, $fieldName, $item['targetDocument']); // @phpstan-ignore-line
        }
    }

    public function supports(MakeFactoryData $makeFactoryData): bool
    {
        try {
            $metadata = $this->getClassMetadata($makeFactoryData);

            return $metadata instanceof ODMClassMetadata;
        } catch (NoPersistenceStrategy) {
            return false;
        }
    }
}
