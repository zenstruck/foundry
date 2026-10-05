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

use Zenstruck\Foundry\Persistence\Relationship\RelationshipMetadata;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
abstract class PersistenceStrategy
{
    /**
     * @param class-string $class
     */
    abstract public function supports(string $class): bool;

    abstract public function persist(object $object): void;

    /**
     * @param class-string $class
     */
    abstract public function flush(string $class): void;

    abstract public function flushAll(): void;

    abstract public function remove(object $object): void;

    abstract public function refresh(object $object): void;

    abstract public function detach(object $object): void;

    /**
     * @template T of object
     *
     * @param class-string<T>      $class
     * @param array<string, mixed> $id
     *
     * @return T|null
     */
    abstract public function find(string $class, array $id): ?object;

    /**
     * @template T of object
     *
     * @param class-string<T>              $class
     * @param array<string, mixed>         $criteria
     * @param array<string, string>|null   $orderBy
     *
     * @return list<T>
     */
    abstract public function findBy(string $class, array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * @param class-string $parent
     * @param class-string $child
     */
    public function bidirectionalRelationshipMetadata(string $parent, string $child, string $field): ?RelationshipMetadata
    {
        return null;
    }

    /**
     * Guard the given class' object manager against computing changesets from
     * uninitialized lazy ghosts, if it cannot handle them natively.
     *
     * @param class-string $class
     */
    public function registerPreFlushGhostInitializer(string $class): void
    {
    }

    /**
     * Whether objects of this strategy can be reset as uninitialized lazy ghosts and refreshed on
     * access. Defaults to true: every Doctrine strategy supports it.
     */
    public function supportsAutoRefresh(): bool
    {
        return true;
    }

    abstract public function hasChanges(object $object): bool;

    abstract public function contains(object $object): bool;

    abstract public function truncate(string $class): void;

    /**
     * @return array<string, mixed>
     */
    abstract public function getIdentifierValues(object $object): array;

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

    /**
     * Removes the given Doctrine listeners immediately and returns a restorer closure.
     *
     * @param class-string       $entityClass
     * @param list<class-string> $disabledClasses [] = disable all, [Foo::class] = disable specific
     *
     * @return callable():void
     */
    abstract public function disableDoctrineEvents(string $entityClass, array $disabledClasses): callable;

    /**
     * Runs the callback in a transaction, when the persistence layer supports it.
     *
     * @template T
     *
     * @param callable():T $callback
     *
     * @return T
     */
    abstract public function transactional(callable $callback): mixed;
}
