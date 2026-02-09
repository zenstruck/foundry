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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ProxyGenerator
{
    private function __construct()
    {
    }

    /**
     * @template T
     *
     * @param T $what
     *
     * @return T
     */
    public static function unwrap(mixed $what): mixed
    {
        if (\is_array($what)) {
            return \array_map(self::unwrap(...), $what); // @phpstan-ignore return.type
        }

        if (\is_object($what) && (new \ReflectionClass($what))->isUninitializedLazyObject($what)) {
            return (new \ReflectionClass($what))->initializeLazyObject($what);
        }

        return $what;
    }
}
