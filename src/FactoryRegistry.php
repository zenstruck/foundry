<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FactoryRegistry
{
    /**
     * @param Factory<mixed>[] $factories
     */
    public function __construct(private iterable $factories)
    {
    }

    /**
     * @template T of Factory
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    public function get(string $class): ?Factory
    {
        foreach ($this->factories as $factory) {
            if ($class === $factory::class) {
                return $factory; // @phpstan-ignore-line
            }
        }

        return null;
    }
}
