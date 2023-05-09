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

namespace Zenstruck\Foundry\Persistence;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Exception\FoundryBootException;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @internal
 */
final class PersistenceManager
{
    private ManagerRegistry|null $managerRegistry = null;

    private bool $flushEnabled = true;

    /**
     * @param class-string $class
     */
    public function objectManagerFor(string $class): ObjectManager
    {
        if (!$objectManager = $this->managerRegistry()->getManagerForClass($class)) {
            throw new \RuntimeException(\sprintf('No object manager registered for "%s".', $class));
        }

        return $objectManager;
    }

    /** @phpstan-assert-if-true !null $this->managerRegistry */
    public function hasManagerRegistry(): bool
    {
        return null !== $this->managerRegistry;
    }

    /**
     * @template T of object
     * @param class-string<T>|T $objectOrClass
     *
     * @return RepositoryProxy<T>
     */
    public function repositoryFor(object|string $objectOrClass): RepositoryProxy
    {
        if ($objectOrClass instanceof Proxy) {
            $objectOrClass = $objectOrClass->object();
        }

        if (!\is_string($objectOrClass)) {
            $objectOrClass = $objectOrClass::class;
        }

        /** @var EntityRepository<T> $repository */
        $repository = $this->managerRegistry()->getRepository($objectOrClass);

        return new RepositoryProxy($repository);
    }

    public function isFlushingEnabled(): bool
    {
        return $this->flushEnabled;
    }

    public function delayFlush(callable $callback): mixed
    {
        $this->flushEnabled = false;

        $result = $callback();

        foreach ($this->managerRegistry()->getManagers() as $manager) {
            $manager->flush();
        }

        $this->flushEnabled = true;

        return $result;
    }

    public function setManagerRegistry(ManagerRegistry $managerRegistry): self
    {
        $this->managerRegistry = $managerRegistry;

        return $this;
    }

    private function managerRegistry(): ManagerRegistry
    {
        if (!$this->hasManagerRegistry()) {
            throw FoundryBootException::notBootedWithDoctrine();
        }

        return $this->managerRegistry;
    }
}
