<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;

/**
 * @internal
 */
final class ChainManagerRegistry implements ManagerRegistry
{
    /** @param list<ManagerRegistry> $managerRegistries */
    public function __construct(private array $managerRegistries)
    {
    }

    public function getRepository($persistentObject, $persistentManagerName = null): ObjectRepository
    {
        foreach ($this->managerRegistries as $managerRegistry) {
            try {
                return $managerRegistry->getRepository($persistentObject, $persistentManagerName);
            } catch (MappingException|ORMMappingException) {
                // the class is not managed by the current manager
            }
        }

        throw new \LogicException("Cannot find repository for class {$persistentObject}");
    }

    public function getManagerForClass($class): ?ObjectManager
    {
        foreach ($this->managerRegistries as $managerRegistry) {
            if ($managerForClass = $managerRegistry->getManagerForClass($class)) {
                return $managerForClass;
            }
        }

        return null;
    }

    /**
     * @return array<string, ObjectManager>
     */
    public function getManagers(): array
    {
        return \array_reduce(
            $this->managerRegistries,
            static fn(array $carry, ManagerRegistry $managerRegistry): array => \array_merge($carry, \array_values($managerRegistry->getManagers())),
            []
        );
    }

    public function getDefaultConnectionName(): string
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getConnection($name = null): object
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getConnections(): array
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getConnectionNames(): array
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getDefaultManagerName(): string
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getManager($name = null): ObjectManager
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function resetManager($name = null): ObjectManager
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    /**
     * @param string $alias
     */
    public function getAliasNamespace($alias): void
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getManagerNames(): array
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }
}
