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
final class FactoryManager
{
    /**
     * @param BaseFactory[] $factories
     */
    public function __construct(private iterable $factories = [])
    {
    }

    /**
     * @template T of BaseFactory
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function create(string $class): BaseFactory
    {
        foreach ($this->factories as $factory) {
            if ($class === $factory::class) {
                return $factory; // @phpstan-ignore-line
            }
        }

        try {
            return new $class();
        } catch (\ArgumentCountError $e) {
            throw new \RuntimeException('Factories with dependencies (Factory services) cannot be used without the foundry bundle.', 0, $e);
        }
    }
}
