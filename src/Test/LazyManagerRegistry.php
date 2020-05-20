<?php

namespace Zenstruck\Foundry\Test;

use Doctrine\Persistence\ManagerRegistry;

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

    public function getDefaultConnectionName()
    {
        return $this->inner()->getDefaultConnectionName();
    }

    public function getConnection($name = null)
    {
        return $this->inner()->getConnection($name);
    }

    public function getConnections()
    {
        return $this->inner()->getConnections();
    }

    public function getConnectionNames()
    {
        return $this->inner()->getConnectionNames();
    }

    public function getDefaultManagerName()
    {
        return $this->inner()->getDefaultManagerName();
    }

    public function getManager($name = null)
    {
        return $this->inner()->getManager($name);
    }

    public function getManagers()
    {
        return $this->inner()->getManagers();
    }

    public function resetManager($name = null)
    {
        return $this->inner()->resetManager($name);
    }

    public function getAliasNamespace($alias)
    {
        return $this->inner()->getAliasNamespace($alias);
    }

    public function getManagerNames()
    {
        return $this->inner()->getManagerNames();
    }

    public function getRepository($persistentObject, $persistentManagerName = null)
    {
        return $this->inner()->getRepository($persistentObject, $persistentManagerName);
    }

    public function getManagerForClass($class)
    {
        return $this->inner()->getManagerForClass($class);
    }

    private function inner(): ManagerRegistry
    {
        return ($this->callback)();
    }
}
