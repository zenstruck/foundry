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

use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\ORM\DoctrineOrmVersionGuesser;
use Zenstruck\Foundry\Persistence\Event\AfterPersist;

/**
 * @internal
 */
final class PersistedObjectsTracker
{
    /**
     * This buffer of objects needs to be static to be kept between two kernel.reset events.
     *
     * @var \WeakMap<object, array<string, mixed>> keys: objects, values: value ids
     */
    private static \WeakMap $trackedObjects;

    public function __construct()
    {
        self::$trackedObjects ??= new \WeakMap();
    }

    public function refresh(): void
    {
        self::resetObjectsAsLazyGhosts();
    }

    /**
     * @param AfterPersist<object> $event
     */
    public function afterPersistHook(AfterPersist $event): void
    {
        if ($event->factory instanceof PersistentProxyObjectFactory || !$event->factory->isAutorefreshEnabled()) {
            return;
        }

        $this->add($event->object);
    }

    public function add(object ...$objects): void
    {
        foreach ($objects as $object) {
            // ie: in-memory objects in a kernel without their persistence strategy
            if (!Configuration::instance()->persistence()->hasPersistenceFor($object)) {
                continue;
            }

            if (self::$trackedObjects->offsetExists($object) && self::$trackedObjects[$object]) {
                if (DoctrineOrmVersionGuesser::isOrmV3()) {
                    self::resetObjectAsLazyGhost($object, self::$trackedObjects[$object]);
                } else {
                    // refresh() would only swap a detached object for its managed instance,
                    // leaving the tracked one stale: rehydrate it in place instead
                    Configuration::instance()->persistence()->autorefresh($object, self::$trackedObjects[$object]);
                }

                continue;
            }

            self::$trackedObjects[$object] = Configuration::instance()->persistence()->getIdentifierValues($object);
        }
    }

    public static function reset(): void
    {
        self::$trackedObjects = new \WeakMap();
    }

    public static function countObjects(): int
    {
        return \count(self::$trackedObjects);
    }

    private static function resetObjectsAsLazyGhosts(): void
    {
        if (!Configuration::isBooted()) {
            return;
        }

        foreach (self::$trackedObjects as $object => $id) {
            if (!$id) {
                continue;
            }

            self::resetObjectAsLazyGhost($object, $id);
        }
    }

    /**
     * @param array<string, mixed> $id
     */
    private static function resetObjectAsLazyGhost(object $object, array $id): void
    {
        $reflector = new \ReflectionClass($object);

        if ($reflector->isUninitializedLazyObject($object)) {
            return;
        }

        // resetAsLazyGhost() destroys the object's state: capture it so it can be
        // restored if the database row no longer exists when the ghost initializes
        $snapshot = (array) $object;
        $reflector->resetAsLazyGhost($object, static function($object) use ($snapshot, $id) {
            // prevent some weird recursion in some edge cases, caused by kernel.reset
            unset(self::$trackedObjects[$object]);

            Configuration::instance()->persistence()->autorefresh($object, $id, $snapshot);

            self::$trackedObjects[$object] = $id;
        });
    }
}
