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
    /** @var list<ManagerRegistry> */
    private $managerRegistries;

    /** @param list<ManagerRegistry> $managerRegistries */
    public function __construct(array $managerRegistries)
    {
        if (0 === \count($managerRegistries)) {
            throw new \InvalidArgumentException('no manager registry provided');
        }

        $this->managerRegistries = $managerRegistries;
    }

    public function getRepository($persistentObject, $persistentManagerName = null): ObjectRepository
    {
        foreach ($this->managerRegistries as $managerRegistry) {
            try {
                if ($repository = $managerRegistry->getRepository($persistentObject, $persistentManagerName)) {
                    return $repository;
                }
            } catch (MappingException $exception) {
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

    public function getManagers(): array
    {
        return \array_reduce(
            $this->managerRegistries,
            static function(array $carry, ManagerRegistry $managerRegistry) {
                return \array_merge($carry, \array_values($managerRegistry->getManagers()));
            },
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

    public function getAliasNamespace($alias): string
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getManagerNames(): array
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }
}
