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
use Zenstruck\Foundry\Persistence\Event\AfterPersist;

/**
 * @internal
 */
final class PersistedObjectsTracker
{
    /**
     * This buffer of objects needs to be static to be kept between two kernel.reset events.
     *
     * @var \WeakMap<object, mixed> keys: objects, values: value ids
     */
    private static \WeakMap $trackedObjects;

    public function __construct()
    {
        self::$trackedObjects ??= new \WeakMap();
    }

    public function refresh(): void
    {
        self::proxifyObjects();
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
            if (self::$trackedObjects->offsetExists($object) && self::$trackedObjects[$object]) {
                self::proxifyObject($object, self::$trackedObjects[$object]);

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

    private static function proxifyObjects(): void
    {
        if (!Configuration::isBooted()) {
            return;
        }

        foreach (self::$trackedObjects as $object => $id) {
            if (!$id) {
                continue;
            }

            self::proxifyObject($object, $id);
        }
    }

    private static function proxifyObject(object $object, mixed $id): void
    {
        $reflector = new \ReflectionClass($object);

        if ($reflector->isUninitializedLazyObject($object)) {
            return;
        }

        $clone = clone $object;
        $reflector->resetAsLazyGhost($object, function($object) use ($clone, $id) {
            Configuration::instance()->persistence()->autorefresh($object, $id, $clone);
        });
    }
}
