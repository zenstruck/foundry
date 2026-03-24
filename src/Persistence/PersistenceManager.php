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

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Exception\PersistenceNotAvailable;
use Zenstruck\Foundry\Object\Hydrator;
use Zenstruck\Foundry\ORM\AbstractORMPersistenceStrategy;
use Zenstruck\Foundry\ORM\DoctrineOrmVersionGuesser;
use Zenstruck\Foundry\Persistence\Exception\NoPersistenceStrategy;
use Zenstruck\Foundry\Persistence\Exception\ObjectHasUnsavedChanges;
use Zenstruck\Foundry\Persistence\Exception\ObjectNoLongerExist;
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

    /** @var list<callable():bool> */
    private array $afterPersistCallbacks = [];

    /**
     * Event listener classes to keep disabled until the next real flush, accumulated across
     * multiple scheduleForInsert() calls inside a flush_after() context.
     * Keyed by spl_object_id of the ObjectManager.
     *
     * @var array<int, list<class-string>|null>
     */
    private array $pendingEventClassesToDisable = [];

    /**
     * Entity-listener overrides to apply during the next real flush, accumulated across
     * multiple scheduleForInsert() calls inside a flush_after() context.
     * Keyed by spl_object_id of the ObjectManager.
     *
     * @var array<int, list<array{entityClass: class-string, disabledClasses: list<class-string>|null}>>
     */
    private array $pendingEntityListenerOverrides = [];

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
     * @param T                       $object
     * @param list<class-string>|null $disabledDoctrineEventClasses null = no events disabled, [] = all disabled, [Foo::class] = specific classes disabled
     *
     * @return T
     */
    public function save(object $object, ?array $disabledDoctrineEventClasses = null): object
    {
        if ($object instanceof Proxy) {
            return $object->_save();
        }

        $om = $this->strategyFor($object::class)->objectManagerFor($object::class);

        // Disable for prePersist (fires during persist())
        // Note: disableEntityListeners must be called first, as it calls getClassMetadata() which
        // triggers the loadClassMetadata event to register #[AsEntityListener] listeners. If we
        // disabled global events first, that event would fire without the EntityListenerRegistry
        // handler, leaving entityListeners permanently empty in the metadata cache.
        $entityListenerBackup = $this->disableEntityListeners($om, $object::class, $disabledDoctrineEventClasses);
        $removedListeners = $this->disableDoctrineEvents($om, $disabledDoctrineEventClasses);

        $om->persist($object);

        $this->restoreDoctrineEvents($om, $removedListeners);
        $this->restoreEntityListeners($om, $object::class, $entityListenerBackup);

        // Disable for postPersist / preUpdate (fire during flush())
        $this->flush($om, $disabledDoctrineEventClasses);

        $shouldFlush = $this->callPostPersistCallbacks();

        if ($shouldFlush) {
            $this->flush($om);
        }

        return $object;
    }

    /**
     * @template T of object
     *
     * @param T                     $object
     * @param list<callable():bool> $afterPersistCallbacks
     * @param list<class-string>|null $disabledDoctrineEventClasses null = no events disabled, [] = all disabled, [Foo::class] = specific classes disabled
     *
     * @return T
     */
    public function scheduleForInsert(object $object, array $afterPersistCallbacks = [], ?array $disabledDoctrineEventClasses = null): object
    {
        if ($object instanceof Proxy) {
            $object = ProxyGenerator::unwrap($object);
        }

        $om = $this->strategyFor($object::class)->objectManagerFor($object::class);

        // Disable for prePersist (fires during persist())
        // Note: disableEntityListeners must be called first — same reason as in save().
        $entityListenerBackup = $this->disableEntityListeners($om, $object::class, $disabledDoctrineEventClasses);
        $removedListeners = $this->disableDoctrineEvents($om, $disabledDoctrineEventClasses);

        $om->persist($object);

        $this->restoreDoctrineEvents($om, $removedListeners);
        $this->restoreEntityListeners($om, $object::class, $entityListenerBackup);

        // Accumulate for postPersist / preUpdate (fire during the deferred flush())
        if (null !== $disabledDoctrineEventClasses) {
            $omId = spl_object_id($om);
            $this->pendingEventClassesToDisable[$omId] = $this->mergeEventClasses(
                $this->pendingEventClassesToDisable[$omId] ?? null,
                $disabledDoctrineEventClasses,
            );
            $this->pendingEntityListenerOverrides[$omId][] = [
                'entityClass' => $object::class,
                'disabledClasses' => $disabledDoctrineEventClasses,
            ];
        }

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

    /**
     * @param list<class-string>|null $eventClassesToDisable
     */
    public function flush(ObjectManager $om, ?array $eventClassesToDisable = null): void
    {
        if (!$this->flush) {
            return;
        }

        $omId = spl_object_id($om);

        // Merge caller-supplied classes with anything accumulated from scheduleForInsert()
        /** @var list<class-string>|null $pendingClasses */
        $pendingClasses = $this->pendingEventClassesToDisable[$omId] ?? null;
        $eventClassesToDisable = $this->mergeEventClasses($eventClassesToDisable, $pendingClasses);
        unset($this->pendingEventClassesToDisable[$omId]);

        $removedListeners = $this->disableDoctrineEvents($om, $eventClassesToDisable);

        // Apply entity-listener overrides accumulated from scheduleForInsert()
        $entityListenerBackups = [];
        foreach ($this->pendingEntityListenerOverrides[$omId] ?? [] as $override) {
            $entityListenerBackups[] = [
                'entityClass' => $override['entityClass'],
                'backup' => $this->disableEntityListeners($om, $override['entityClass'], $override['disabledClasses']),
            ];
        }
        unset($this->pendingEntityListenerOverrides[$omId]);

        $om->flush();

        $this->restoreDoctrineEvents($om, $removedListeners);
        foreach ($entityListenerBackups as ['entityClass' => $entityClass, 'backup' => $backup]) {
            $this->restoreEntityListeners($om, $entityClass, $backup);
        }
    }

    /**
     * @template T of object
     *
     * @param T $object
     */
    public function autorefresh(object $object, mixed $id, object $clone): void
    {
        $strategy = $this->strategyFor($object::class);
        $om = $strategy->objectManagerFor($object::class);

        if ($id) {
            try {
                $om->refresh($object);

                if ($this->getIdentifierValues($object)) {
                    // no identifier values means the object no longer exists
                    return;
                }
            } catch (\Throwable $e) {
            }

            // let's detach the object, in order to prevent Doctrine cache
            $om->detach($object);
            if ($refreshedObject = $om->find($object::class, $id)) {
                if (!DoctrineOrmVersionGuesser::isOrmV3()) {
                    $this->refresh($refreshedObject, canThrow: false);
                }

                Hydrator::hydrateFromOtherObject($object, $refreshedObject);

                return;
            }
        }

        // the object no longer exists
        Hydrator::hydrateFromOtherObject($object, $clone);
        $om->detach($object);
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return T
     *
     * @throws RefreshObjectFailed
     */
    public function refresh(object &$object, bool $force = false, bool $canThrow = true): object
    {
        if (!$this->flush && !$force) {
            return $object;
        }

        if ($object instanceof Proxy) {
            return $object->_refresh();
        }

        if (
            \PHP_VERSION_ID >= 80400
            && ($reflector = new \ReflectionClass($object))->isUninitializedLazyObject($object)
        ) {
            /** @var T $object */
            $object = $reflector->initializeLazyObject($object);
        }

        $strategy = $this->strategyFor($object::class);

        if ($strategy->isEmbeddable($object)) {
            return $object;
        }

        if ($strategy->hasChanges($object)) {
            if (!$canThrow) {
                return $object;
            }

            throw new ObjectHasUnsavedChanges($object::class);
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

        $id = $strategy->getIdentifierValues($object);

        if (!$id || !($objectFromDB = $om->find($object::class, $id))) {
            if (!$canThrow) {
                return $object;
            }

            throw new ObjectNoLongerExist($object);
        }

        $object = $objectFromDB;

        return $object;
    }

    public function isPersisted(object $object): bool
    {
        if ($object instanceof Proxy) {
            $object = $object->_real(withAutoRefresh: false);
        }

        if (
            \PHP_VERSION_ID >= 80400
            && ($reflector = new \ReflectionClass($object))->isUninitializedLazyObject($object)
        ) {
            /** @var object $object */
            $object = $reflector->initializeLazyObject($object);
        }

        $persistenceStrategy = $this->strategyFor($object::class);

        // prevents doctrine to use its cache and think the object is persisted
        if ($persistenceStrategy->isScheduledForInsert($object)) {
            return false;
        }

        if ($object instanceof Proxy) {
            $object = ProxyGenerator::unwrap($object);
        }

        $om = $persistenceStrategy->objectManagerFor($object::class);
        $id = $persistenceStrategy->getIdentifierValues($object);

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

        if (
            \PHP_VERSION_ID >= 80400
            && ($reflector = new \ReflectionClass($object))->isUninitializedLazyObject($object)
        ) {
            /** @var T $object */
            $object = $reflector->initializeLazyObject($object);
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
        $class = ProxyGenerator::unwrap($class);

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
        $class = ProxyGenerator::unwrap($class);

        return $this->strategyFor($class)->objectManagerFor($class)->getRepository($class);
    }

    /**
     * @param class-string $parent
     * @param class-string $child
     */
    public function bidirectionalRelationshipMetadata(string $parent, string $child, string $field): ?RelationshipMetadata
    {
        $parent = ProxyGenerator::unwrap($parent);
        $child = ProxyGenerator::unwrap($child);

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
        $owner = ProxyGenerator::unwrap($owner);

        try {
            return $this->strategyFor($owner)->embeddablePropertiesFor(ProxyGenerator::unwrap($object), $owner);
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

    public function getIdentifierValues(object $object): mixed
    {
        return $this->strategyFor($object::class)->getIdentifierValues($object);
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

        $shouldFlush = false;

        foreach ($afterPersistCallbacks as $afterPersistCallback) {
            if ($afterPersistCallback()) {
                $shouldFlush = true;
            }
        }

        return $shouldFlush;
    }

    /**
     * Temporarily removes Doctrine event listeners from the EventManager.
     *
     * @param list<class-string>|null $eventClassesToDisable null = nothing removed, [] = all removed, [Foo::class] = specific classes removed
     *
     * @return array<string, list<object>> map of eventName => removed listeners, for later restoration
     */
    private function disableDoctrineEvents(ObjectManager $om, ?array $eventClassesToDisable): array
    {
        if (null === $eventClassesToDisable || !method_exists($om, 'getEventManager')) {
            return [];
        }

        /** @var EventManager $eventManager */
        $eventManager = $om->getEventManager();
        $removed = [];

        foreach ($eventManager->getAllListeners() as $eventName => $listeners) {
            foreach ($listeners as $listener) {
                if ([] === $eventClassesToDisable || \in_array($listener::class, $eventClassesToDisable, true)) {
                    $eventManager->removeEventListener([$eventName], $listener);
                    $removed[$eventName][] = $listener;
                }
            }
        }

        return $removed;
    }

    /**
     * Re-adds Doctrine event listeners previously removed by disableDoctrineEvents().
     *
     * @param array<string, list<object>> $removedListeners
     */
    private function restoreDoctrineEvents(ObjectManager $om, array $removedListeners): void
    {
        if ([] === $removedListeners || !method_exists($om, 'getEventManager')) {
            return;
        }

        /** @var EventManager $eventManager */
        $eventManager = $om->getEventManager();

        foreach ($removedListeners as $eventName => $listeners) {
            foreach ($listeners as $listener) {
                $eventManager->addEventListener([$eventName], $listener);
            }
        }
    }

    /**
     * Temporarily removes Doctrine entity listeners by modifying the entity's ClassMetadata.
     *
     * @param class-string            $entityClass
     * @param list<class-string>|null $eventClassesToDisable null = nothing removed, [] = all removed, [Foo::class] = specific classes removed
     *
     * @return array<string, list<array{class: class-string, method: string}>> original entityListeners for later restoration
     */
    private function disableEntityListeners(ObjectManager $om, string $entityClass, ?array $eventClassesToDisable): array
    {
        if (null === $eventClassesToDisable || !$om instanceof EntityManagerInterface) {
            return [];
        }

        $metadata = $om->getClassMetadata($entityClass);
        $original = $metadata->entityListeners;

        if ([] === $original) {
            return [];
        }

        if ([] === $eventClassesToDisable) {
            $metadata->entityListeners = [];
        } else {
            $filtered = [];
            foreach ($original as $event => $listeners) {
                foreach ($listeners as $listener) {
                    if (!\in_array($listener['class'], $eventClassesToDisable, true)) {
                        $filtered[$event][] = $listener;
                    }
                }
            }
            $metadata->entityListeners = $filtered;
        }

        return $original;
    }

    /**
     * Restores entity listeners previously removed by disableEntityListeners().
     *
     * @param class-string                                                        $entityClass
     * @param array<string, list<array{class: class-string, method: string}>>     $original
     */
    private function restoreEntityListeners(ObjectManager $om, string $entityClass, array $original): void
    {
        if ([] === $original || !$om instanceof EntityManagerInterface) {
            return;
        }

        $om->getClassMetadata($entityClass)->entityListeners = $original;
    }

    /**
     * Merges two sets of event classes to disable, following these rules:
     *   - null + anything  = anything  (null means "no disabling requested")
     *   - []   + anything  = []        ([] means "disable all", takes precedence)
     *   - [A]  + [B]       = [A, B]    (union of specific classes)
     *
     * @param list<class-string>|null $a
     * @param list<class-string>|null $b
     *
     * @return list<class-string>|null
     */
    private function mergeEventClasses(?array $a, ?array $b): ?array
    {
        if (null === $a) {
            return $b;
        }

        if (null === $b) {
            return $a;
        }

        if ([] === $a || [] === $b) {
            return [];
        }

        return \array_values(\array_unique([...$a, ...$b]));
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
