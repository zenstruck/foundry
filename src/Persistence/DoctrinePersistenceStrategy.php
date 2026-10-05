<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectManager;

/**
 * Carries everything Doctrine-specific: the registry, the object managers, and the operations
 * delegated to them.
 *
 * @internal
 */
abstract class DoctrinePersistenceStrategy extends PersistenceStrategy implements ProvidesMetadata
{
    public function __construct(protected readonly ManagerRegistry $registry)
    {
    }

    public function supports(string $class): bool
    {
        return (bool) $this->registry->getManagerForClass($class);
    }

    /**
     * @param class-string $class
     */
    public function objectManagerFor(string $class): ObjectManager
    {
        return $this->registry->getManagerForClass($class) ?? throw new \LogicException(\sprintf('No manager found for "%s".', $class));
    }

    /**
     * @return ObjectManager[]
     */
    public function objectManagers(): array
    {
        return $this->registry->getManagers();
    }

    public function persist(object $object): void
    {
        $this->objectManagerFor($object::class)->persist($object);
    }

    public function flush(string $class): void
    {
        $this->objectManagerFor($class)->flush();
    }

    public function flushAll(): void
    {
        foreach ($this->objectManagers() as $objectManager) {
            $objectManager->flush();
        }
    }

    public function remove(object $object): void
    {
        $this->objectManagerFor($object::class)->remove($object);
    }

    public function refresh(object $object): void
    {
        $this->objectManagerFor($object::class)->refresh($object);
    }

    public function detach(object $object): void
    {
        $this->objectManagerFor($object::class)->detach($object);
    }

    public function find(string $class, array $id): ?object
    {
        return $this->objectManagerFor($class)->find($class, $id);
    }

    public function findBy(string $class, array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return \array_values($this->objectManagerFor($class)->getRepository($class)->findBy($criteria, $orderBy, $limit, $offset));
    }

    /**
     * @throws MappingException If $class is not managed by Doctrine
     */
    public function classMetadata(string $class): ClassMetadata
    {
        return $this->objectManagerFor($class)->getClassMetadata($class);
    }

    public function allMetadata(): iterable
    {
        foreach ($this->objectManagers() as $objectManager) {
            yield from $objectManager->getMetadataFactory()->getAllMetadata();
        }
    }
}
