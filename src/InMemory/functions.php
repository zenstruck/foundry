<?php

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
 * @param class-string $class
 *
 * @internal
 */
function should_enable_in_memory(string $class, string $method): bool
{
    $classReflection = new \ReflectionClass($class);

    if ($classReflection->getAttributes(AsInMemoryTest::class)) {
        return true;
    }

    return (bool)$classReflection->getMethod($method)->getAttributes(AsInMemoryTest::class);
}
