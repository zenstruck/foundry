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

namespace Zenstruck\Foundry\Maker\Factory;

use Doctrine\ORM\Mapping\ClassMetadataInfo as ORMClassMetadata;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zenstruck\Foundry\ORM\DoctrineOrmVersionGuesser;
use Zenstruck\Foundry\Persistence\Exception\NoPersistenceStrategy;

/**
 * @internal
 * @see ORMDefaultPropertiesGuesser
 *
 * This file is basically a copy/paste of ORMDefaultPropertiesGuesser, but offers doctrine/orm 2 compatibility
 */
final class LegacyORMDefaultPropertiesGuesser extends AbstractDoctrineDefaultPropertiesGuesser
{
    public function __invoke(SymfonyStyle $io, MakeFactoryData $makeFactoryData, MakeFactoryQuery $makeFactoryQuery): void
    {
        if (DoctrineOrmVersionGuesser::isOrmV3()) {
            return;
        }

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
        } catch (NoPersistenceStrategy) {
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
