<?php

namespace Zenstruck\Foundry;

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
                if ($repository = $managerRegistry->getRepository($persistentObject, $persistentManagerName)) {
                    return $repository;
                }
            } catch (MappingException) {
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

    public function getDefaultConnectionName(): void
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getConnection($name = null): void
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getConnections(): void
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getConnectionNames(): void
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getDefaultManagerName(): void
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getManager($name = null): void
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function resetManager($name = null): void
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getAliasNamespace($alias): void
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getManagerNames(): void
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }
}
