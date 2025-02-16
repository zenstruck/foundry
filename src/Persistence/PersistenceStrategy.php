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
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class PersistenceStrategy
{
    public function __construct(protected readonly ManagerRegistry $registry)
    {
    }

    /**
     * @param class-string $class
     */
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

    /**
     * @param class-string $parent
     * @param class-string $child
     */
    public function inversedRelationshipMetadata(string $parent, string $child, string $field): ?InverseRelationshipMetadata
    {
        return null;
    }

    /**
     * @template T of object
     * @param  class-string<T>  $class
     * @return ClassMetadata<T>
     *
     * @throws MappingException If $class is not managed by Doctrine
     */
    public function classMetadata(string $class): ClassMetadata
    {
        return $this->objectManagerFor($class)->getClassMetadata($class);
    }

    abstract public function hasChanges(object $object): bool;

    abstract public function contains(object $object): bool;

    abstract public function truncate(string $class): void;

    /**
     * @return list<string>
     */
    abstract public function managedNamespaces(): array;

    /**
     * @param class-string $owner
     *
     * @return array<string,mixed>|null
     */
    abstract public function embeddablePropertiesFor(object $object, string $owner): ?array;

    abstract public function isEmbeddable(object $object): bool;

    abstract public function isScheduledForInsert(object $object): bool;
}
