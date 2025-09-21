<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence\Proxy;

use Zenstruck\Foundry\Configuration;

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
    private static \WeakMap $buffer;

    public function __construct()
    {
        self::$buffer ??= new \WeakMap();
    }

    public function refresh(): void
    {
        self::proxifyObjects();
    }

    public function add(object ...$objects): void
    {
        foreach ($objects as $object) {
            if (self::$buffer->offsetExists($object) && self::$buffer[$object]) {
                continue;
            }

            self::$buffer[$object] = Configuration::instance()->persistence()->getIdentifierValues($object);
        }
    }

    public static function updateIds(): void
    {
        foreach (self::$buffer as $object => $id) {
            if ($id) {
                continue;
            }

            self::$buffer[$object] = Configuration::instance()->persistence()->getIdentifierValues($object);
        }
    }

    public static function reset(): void
    {
        self::$buffer = new \WeakMap();
    }

    public static function countObjects(): int
    {
        return \count(self::$buffer);
    }

    private static function proxifyObjects(): void
    {
        if (!Configuration::isBooted()) {
            return;
        }

        foreach (self::$buffer as $object => $id) {
            if (!$id) {
                continue;
            }

            $reflector = new \ReflectionClass($object);

            if ($reflector->isUninitializedLazyObject($object)) {
                continue;
            }

            $clone = clone $object;
            $reflector->resetAsLazyGhost($object, function($object) use ($clone, $id) {
                Configuration::instance()->persistence()->autorefresh($object, $id, $clone);
            });
        }
    }
}
