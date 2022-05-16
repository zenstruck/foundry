<?php

namespace Zenstruck\Foundry\Test;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyManagerRegistry implements ManagerRegistry
{
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function getDefaultConnectionName(): string
    {
        return $this->inner()->getDefaultConnectionName();
    }

    public function getConnection($name = null): object
    {
        return $this->inner()->getConnection($name);
    }

    public function getConnections(): array
    {
        return $this->inner()->getConnections();
    }

    public function getConnectionNames(): array
    {
        return $this->inner()->getConnectionNames();
    }

    public function getDefaultManagerName(): string
    {
        return $this->inner()->getDefaultManagerName();
    }

    public function getManager($name = null): ObjectManager
    {
        return $this->inner()->getManager($name);
    }

    public function getManagers(): array
    {
        return $this->inner()->getManagers();
    }

    public function resetManager($name = null): ObjectManager
    {
        return $this->inner()->resetManager($name);
    }

    public function getAliasNamespace($alias): string
    {
        $inner = $this->inner();

        if (\method_exists($inner, 'getAliasNamespace')) {
            return $inner->getAliasNamespace($alias);
        }

        throw new \BadMethodCallException('Method removed in doctrine/persistence v3.');
    }

    public function getManagerNames(): array
    {
        return $this->inner()->getManagerNames();
    }

    public function getRepository($persistentObject, $persistentManagerName = null): ObjectRepository
    {
        return $this->inner()->getRepository($persistentObject, $persistentManagerName);
    }

    public function getManagerForClass($class): ?ObjectManager
    {
        return $this->inner()->getManagerForClass($class);
    }

    private function inner(): ManagerRegistry
    {
        return ($this->callback)();
    }
}
