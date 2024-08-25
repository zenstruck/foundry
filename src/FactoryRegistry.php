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

use Zenstruck\Foundry\Exception\CannotCreateFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class FactoryRegistry implements FactoryRegistryInterface
{
    /**
     * @param Factory<mixed>[] $factories
     */
    public function __construct(private iterable $factories)
    {
    }

    public function get(string $class): Factory
    {
        foreach ($this->factories as $factory) {
            if ($class === $factory::class) {
                return $factory; // @phpstan-ignore-line
            }
        }

        try {
            return new $class();
        } catch (\ArgumentCountError $e) {
            throw CannotCreateFactory::argumentCountError($e);
        }
    }
}
