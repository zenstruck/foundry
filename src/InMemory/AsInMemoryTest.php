<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\InMemory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @experimental
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class AsInMemoryTest
{
    /**
     * @param class-string $class
     * @internal
     */
    public static function shouldEnableInMemory(string $class, string $method): bool
    {
        $classReflection = new \ReflectionClass($class);

        if ($classReflection->getAttributes(static::class)) {
            return true;
        }

        return (bool) $classReflection->getMethod($method)->getAttributes(static::class);
    }
}
