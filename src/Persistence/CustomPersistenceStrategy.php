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

use function Zenstruck\Foundry\get;

/**
 * Base class for a non-Doctrine backend. Four methods are left to implement -- supports(),
 * persist(), findBy() and identifierFields() -- and everything Foundry can answer on the backend's
 * behalf is answered here.
 *
 * A backend writing straight away leaves flush() alone. One that would rather send a single batch
 * buffers in persist() and sends it in flush(): Foundry decides when flush() happens, so flush_after()
 * keeps its promise either way.
 */
abstract class CustomPersistenceStrategy extends PersistenceStrategy
{
    /**
     * The fields holding this class' identity, eg. ['id'].
     *
     * @param class-string $class
     *
     * @return list<string>
     */
    abstract protected function identifierFields(string $class): array;

    final public function getIdentifierValues(object $object): array
    {
        $fields = $this->identifierFields($object::class);

        return \array_combine($fields, \array_map(static fn(string $field) => get($object, $field), $fields));
    }

    final public function find(string $class, array $id): ?object
    {
        return $this->findBy($class, $id, limit: 1)[0] ?? null;
    }

    public function flush(string $class): void
    {
    }

    public function flushAll(): void
    {
    }

    /**
     * A backend without transactions just runs the callback: flush_after() keeps its promise
     * either way, only the rollback on failure is lost.
     *
     * @template T
     *
     * @param callable():T $callback
     *
     * @return T
     */
    public function transactional(callable $callback): mixed
    {
        return $callback();
    }

    public function remove(object $object): void
    {
        throw new \LogicException(\sprintf('"%s" cannot delete objects.', static::class));
    }

    public function truncate(string $class): void
    {
        throw new \LogicException(\sprintf('"%s" cannot truncate "%s".', static::class, $class));
    }

    public function disablePersistenceEvents(string $class, array $disabledClasses): callable
    {
        throw new \LogicException(\sprintf('"%s" has no lifecycle events to disable.', static::class));
    }

    final public function refresh(object $object): void
    {
    }

    final public function detach(object $object): void
    {
    }

    /**
     * Objects are never detached from a backend Foundry does not track, so they always count as
     * managed and never as dirty.
     */
    final public function contains(object $object): bool
    {
        return true;
    }

    final public function hasChanges(object $object): bool
    {
        return false;
    }

    final public function managedNamespaces(): array
    {
        return [];
    }

    final public function isEmbeddable(object $object): bool
    {
        return false;
    }

    final public function isScheduledForInsert(object $object): bool
    {
        return false;
    }

    final public function embeddablePropertiesFor(object $object, string $owner): ?array
    {
        return null;
    }

    /**
     * Not final: a backend layered over an existing mapping (the in-memory mode over Doctrine
     * entities, say) replaces persistence without replacing the mapping, and relations must keep
     * being wired.
     */
    public function bidirectionalRelationshipMetadata(string $parent, string $child, string $field): ?RelationshipMetadata
    {
        return null;
    }

    final public function supportsAutoRefresh(): bool
    {
        return false;
    }
}
