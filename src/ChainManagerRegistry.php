<?php

namespace Zenstruck\Foundry;

use Doctrine\Persistence\ManagerRegistry;
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
            if ($repository = $managerRegistry->getRepository($persistentObject, $persistentManagerName)) {
                return $repository;
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
                return \array_merge($carry, $managerRegistry->getManagers());
            },
            []
        );
    }

    public function getDefaultConnectionName()
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getConnection($name = null)
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getConnections()
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getConnectionNames()
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getDefaultManagerName()
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getManager($name = null)
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function resetManager($name = null)
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getAliasNamespace($alias)
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }

    public function getManagerNames()
    {
        throw new \BadMethodCallException('Not available in '.self::class);
    }
}
