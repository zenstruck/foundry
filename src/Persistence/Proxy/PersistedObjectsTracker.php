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
     * @var \WeakMap<object, bool>
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
            if (self::$buffer->offsetExists($object)) {
                continue;
            }

            self::$buffer[$object] = true;
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
        foreach (self::$buffer as $object => $_) {
            $reflector = new \ReflectionClass($object);

            if ($reflector->isUninitializedLazyObject($object)) {
                continue;
            }

            $clone = clone $object;
            $reflector->resetAsLazyGhost($object, function($object) use ($clone) {
                Configuration::instance()->persistence()->autorefresh($object, $clone);
            });
        }
    }
}
