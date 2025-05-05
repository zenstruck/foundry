<?php

namespace Zenstruck\Foundry\Persistence\Proxy;

use WeakReference;

use function Zenstruck\Foundry\Persistence\refresh;

final class CreatedObjectsTracker
{
    /** @var list<\WeakReference<object>> */
    private static $buffer = [];

    public static function add(object $object): void
    {
        self::$buffer[] = \WeakReference::create($object);
    }

    public static function proxifyObjects(): void
    {
        self::$buffer = array_values(
            array_map(
                static function (WeakReference $weakRef) {
                    $object = $weakRef->get() ?? throw new \LogicException('Object cannot be null.');
                    $clone = clone $object;
                    (new \ReflectionClass($object))->resetAsLazyProxy($object, fn() => refresh($clone));

                    return \WeakReference::create($object);
                },
                array_filter(self::$buffer, static fn (WeakReference $weakRef) => $weakRef->get() !== null),
            )
        );
    }

    public static function reset(): void
    {
        self::$buffer = [];
    }

    public static function countObjects(): int
    {
        return \count(self::$buffer);
    }

    public static function countObjectsWithValidRef(): int
    {
        return \count(
            array_filter(self::$buffer, static fn (WeakReference $weakRef) => $weakRef->get() !== null)
        );
    }
}
