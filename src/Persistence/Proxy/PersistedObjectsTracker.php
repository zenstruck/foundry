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
     * @var list<\WeakReference<object>>
     */
    private static array $buffer = [];

    /**
     * @var \WeakMap<object, mixed>
     */
    private static \WeakMap $ids;

    public function __construct()
    {
        self::$ids ??= new \WeakMap();
    }

    public function refresh(): void
    {
        self::proxifyObjects();
    }

    public function add(object ...$objects): void
    {
        foreach ($objects as $object) {
            self::$buffer[] = \WeakReference::create($object);

            $id = Configuration::instance()->persistence()->getIdentifierValues($object);
            if ($id) {
                self::$ids[$object] = $id;
            }
        }
    }

    public static function updateIds(): void
    {
        foreach (self::$buffer as $reference) {
            if (null === $object = $reference->get()) {
                continue;
            }

            if (self::$ids->offsetExists($object)) {
                continue;
            }

            self::$ids[$object] = Configuration::instance()->persistence()->getIdentifierValues($object);
        }
    }

    public static function reset(): void
    {
        self::$buffer = [];
        self::$ids = new \WeakMap();
    }

    public static function countObjects(): int
    {
        return \count(
            \array_filter(self::$buffer, static fn(\WeakReference $weakRef) => null !== $weakRef->get())
        );
    }

    private static function proxifyObjects(): void
    {
        if (!Configuration::isBooted()) {
            return;
        }

        self::$buffer = \array_values(
            \array_map(
                static function(\WeakReference $weakRef) {
                    $object = $weakRef->get() ?? throw new \LogicException('Object cannot be null.');

                    $reflector = new \ReflectionClass($object);

                    if ($reflector->isUninitializedLazyObject($object)) {
                        return \WeakReference::create($object);
                    }

                    $clone = clone $object;
                    $reflector->resetAsLazyGhost($object, function($object) use ($clone) {
                        $id = self::$ids[$object] ?? throw new \LogicException('Canot find the id for object');

                        Configuration::instance()->persistence()->autorefresh($object, $id, $clone);
                    });

                    return \WeakReference::create($object);
                },

                // remove all empty references
                \array_filter(self::$buffer, static fn(\WeakReference $weakRef) => null !== $weakRef->get()),
            )
        );
    }
}
