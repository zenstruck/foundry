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

use Doctrine\ORM\Mapping\ClassMetadataInfo as ORMClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;

/**
 * @internal
 */
final class FactoryCandidatesClassesExtractor
{
    public function __construct(private ManagerRegistry $managerRegistry, private FactoryClassMap $factoryClassMap)
    {
    }

    public function factoryCandidatesClasses(): array
    {
        $choices = [];

        $embeddedClasses = [];

        foreach ($this->managerRegistry->getManagers() as $manager) {
            foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
                if ($metadata->getReflectionClass()->isAbstract()) {
                    continue;
                }

                if (!$this->factoryClassMap->classHasFactory($metadata->getName())) {
                    $choices[] = $metadata->getName();
                }

                $embeddedClasses[] = $this->findEmbeddedClasses($metadata);
            }
        }

        $choices = [
            ...$choices,
            ...\array_values(\array_unique(\array_merge(...$embeddedClasses))),
        ];

        \sort($choices);

        if (empty($choices)) {
            throw new RuntimeCommandException('No entities or documents found, or none left to make factories for.');
        }

        return $choices;
    }

    /**
     * @return list<string>
     */
    private function findEmbeddedClasses(ClassMetadata $metadata): array
    {
        // - Doctrine ORM embedded objects does NOT have metadata classes, so we have to find all embedded classes inside entities
        // - Doctrine ODM embedded objects HAVE metadata classes, so they are already returned by factoryCandidatesClasses()
        return match (true) {
            $metadata instanceof ORMClassMetadata => \array_column($metadata->embeddedClasses, 'class'),
            default => [],
        };
    }
}
