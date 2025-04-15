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

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Exception\PersistenceNotAvailable;
use Zenstruck\Foundry\ORM\AbstractORMPersistenceStrategy;
use Zenstruck\Foundry\Persistence\Exception\NoPersistenceStrategy;
use Zenstruck\Foundry\Persistence\Exception\RefreshObjectFailed;
use Zenstruck\Foundry\Persistence\Relationship\RelationshipMetadata;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class PersistenceManager
{
    private bool $flush = true;
    private bool $persist = true;

    /** @var list<callable():void> */
    private array $afterPersistCallbacks = [];

    /**
     * @param iterable<PersistenceStrategy> $strategies
     */
    public function __construct(
        private iterable $strategies,
        private ResetDatabaseManager $resetDatabaseManager,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->persist;
    }

    public function disablePersisting(): void
    {
        $this->persist = false;
    }

    public function enablePersisting(): void
    {
        $this->persist = true;
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return T
     */
    public function save(object $object): object
    {
        if ($object instanceof Proxy) {
            return $object->_save();
        }

        $om = $this->strategyFor($object::class)->objectManagerFor($object::class);
        $om->persist($object);
        $this->flush($om);

        $callbacksCalled = $this->callPostPersistCallbacks();

        if ($callbacksCalled) {
            $this->flush($om);
        }

        return $object;
    }

    /**
     * @template T of object
     *
     * @param T                     $object
     * @param list<callable():void> $afterPersistCallbacks
     *
     * @return T
     */
    public function scheduleForInsert(object $object, array $afterPersistCallbacks = []): object
    {
        if ($object instanceof Proxy) {
            $object = unproxy($object);
        }

        $om = $this->strategyFor($object::class)->objectManagerFor($object::class);
        $om->persist($object);

        $this->afterPersistCallbacks = [...$this->afterPersistCallbacks, ...$afterPersistCallbacks];

        return $object;
    }

    /**
     * @template T
     *
     * @param callable():T $callback
     *
     * @return T
     */
    public function flushAfter(callable $callback): mixed
    {
        $this->flush = false;

        $result = $callback();

        $this->flush = true;

        $this->flushAllStrategies();

        $callbacksCalled = $this->callPostPersistCallbacks();

        if ($callbacksCalled) {
            $this->flushAllStrategies();
        }

        return $result;
    }

    public function flush(ObjectManager $om): void
    {
        if ($this->flush) {
            $om->flush();
        }
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return T
     */
    public function refresh(object &$object, bool $force = false): object
    {
        if (!$this->flush && !$force) {
            return $object;
        }

        if ($object instanceof Proxy) {
            return $object->_refresh();
        }

        $strategy = $this->strategyFor($object::class);

        if ($strategy->hasChanges($object)) {
            throw RefreshObjectFailed::objectHasUnsavedChanges($object::class);
        }

        $om = $strategy->objectManagerFor($object::class);

        if ($strategy->contains($object)) {
            try {
                $om->refresh($object);
            } catch (\LogicException|\Error) {
                // prevent entities/documents with readonly properties to create an error
                // LogicException is for ORM / Error is for ODM
                // @see https://github.com/doctrine/orm/issues/9505
            }

            return $object;
        }

        if ($strategy->isEmbeddable($object)) {
            return $object;
        }

        $id = $om->getClassMetadata($object::class)->getIdentifierValues($object);

        if (!$id || !($object = $om->find($object::class, $id))) { // @phpstan-ignore parameterByRef.type
            throw RefreshObjectFailed::objectNoLongExists();
        }

        return $object;
    }

    public function isPersisted(object $object): bool
    {
        // prevents doctrine to use its cache and think the object is persisted
        if ($this->strategyFor($object::class)->isScheduledForInsert($object)) {
            return false;
        }

        if ($object instanceof Proxy) {
            $object = unproxy($object);
        }

        $om = $this->strategyFor($object::class)->objectManagerFor($object::class);
        $id = $om->getClassMetadata($object::class)->getIdentifierValues($object);

        return $id && null !== $om->find($object::class, $id);
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return T
     */
    public function delete(object $object): object
    {
        if ($object instanceof Proxy) {
            return $object->_delete();
        }

        $om = $this->strategyFor($object::class)->objectManagerFor($object::class);
        $om->remove($object);
        $this->flush($om);

        return $object;
    }

    /**
     * @param class-string $class
     */
    public function truncate(string $class): void
    {
        $class = unproxy($class);

        $this->strategyFor($class)->truncate($class);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return ObjectRepository<T>
     */
    public function repositoryFor(string $class): ObjectRepository
    {
        $class = unproxy($class);

        return $this->strategyFor($class)->objectManagerFor($class)->getRepository($class);
    }

    /**
     * @param class-string $parent
     * @param class-string $child
     */
    public function bidirectionalRelationshipMetadata(string $parent, string $child, string $field): ?RelationshipMetadata
    {
        $parent = unproxy($parent);
        $child = unproxy($child);

        return $this->strategyFor($parent)->bidirectionalRelationshipMetadata($parent, $child, $field);
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return ClassMetadata<T>
     */
    public function metadataFor(string $class): ClassMetadata
    {
        return $this->strategyFor($class)->classMetadata($class);
    }

    /**
     * @return iterable<ClassMetadata<object>>
     */
    public function allMetadata(): iterable
    {
        foreach ($this->strategies as $strategy) {
            foreach ($strategy->objectManagers() as $objectManager) {
                yield from $objectManager->getMetadataFactory()->getAllMetadata();
            }
        }
    }

    /**
     * @return list<string>
     */
    public function managedNamespaces(): array
    {
        $namespaces = [];

        foreach ($this->strategies as $strategy) {
            $namespaces[] = $strategy->managedNamespaces();
        }

        return \array_values(\array_unique(\array_merge(...$namespaces)));
    }

    /**
     * @param class-string $owner
     *
     * @return array<string,mixed>|null
     */
    public function embeddablePropertiesFor(object $object, string $owner): ?array
    {
        $owner = unproxy($owner);

        try {
            return $this->strategyFor($owner)->embeddablePropertiesFor(unproxy($object), $owner);
        } catch (NoPersistenceStrategy) {
            return null;
        }
    }

    public function hasPersistenceFor(object $object): bool
    {
        try {
            $strategy = $this->strategyFor($object::class);

            return !$strategy->isEmbeddable($object);
        } catch (NoPersistenceStrategy) {
            return false;
        }
    }

    public function resetDatabaseManager(): ResetDatabaseManager
    {
        return $this->resetDatabaseManager;
    }

    public static function isOrmOnly(): bool
    {
        static $isOrmOnly = null;

        return $isOrmOnly ??= (static function(): bool {
            try {
                $strategies = \iterator_to_array(Configuration::instance()->persistence()->strategies);
            } catch (PersistenceNotAvailable) {
                $strategies = [];
            }

            return 1 === \count($strategies) && $strategies[0] instanceof AbstractORMPersistenceStrategy;
        })();
    }

    private function flushAllStrategies(): void
    {
        foreach ($this->strategies as $strategy) {
            foreach ($strategy->objectManagers() as $om) {
                $this->flush($om);
            }
        }
    }

    /**
     * @return bool whether or not some callbacks were called
     */
    private function callPostPersistCallbacks(): bool
    {
        if (!$this->flush || [] === $this->afterPersistCallbacks) {
            return false;
        }

        $afterPersistCallbacks = $this->afterPersistCallbacks;
        $this->afterPersistCallbacks = [];

        foreach ($afterPersistCallbacks as $afterPersistCallback) {
            $afterPersistCallback();
        }

        return true;
    }

    /**
     * @param class-string $class
     *
     * @throws NoPersistenceStrategy if no persistence strategy found
     */
    private function strategyFor(string $class): PersistenceStrategy
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($class)) {
                return $strategy;
            }
        }

        throw new NoPersistenceStrategy($class);
    }
}
