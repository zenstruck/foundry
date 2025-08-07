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
    private static $buffer = [];

    public function refresh(): void
    {
        self::proxifyObjects();
    }

    public function add(object ...$objects): void
    {
        foreach ($objects as $object) {
            self::$buffer[] = \WeakReference::create($object);
        }
    }

    public function reset(): void
    {
        self::$buffer = [];
    }

    public static function countObjects(): int
    {
        return \count(
            \array_filter(self::$buffer, static fn(\WeakReference $weakRef) => null !== $weakRef->get())
        );
    }

    private static function proxifyObjects(): void
    {
        self::$buffer = \array_values(
            \array_map(
                static function(\WeakReference $weakRef) {
                    $object = $weakRef->get() ?? throw new \LogicException('Object cannot be null.');

                    $reflector = new \ReflectionClass($object);

                    if ($reflector->isUninitializedLazyObject($object)) {
                        return \WeakReference::create($object);
                    }

                    $clone = clone $object;
                    $reflector->resetAsLazyProxy($object, function() use ($clone, $reflector) {
                        Configuration::instance()->persistence()->refresh($clone, allowRefreshDeletedObject: true);

                        return $reflector->initializeLazyObject($clone);
                    });

                    return \WeakReference::create($object);
                },

                // remove all empty references
                \array_filter(self::$buffer, static fn(\WeakReference $weakRef) => null !== $weakRef->get()),
            )
        );
    }
}
