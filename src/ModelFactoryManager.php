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

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ModelFactoryManager
{
    /**
     * @param PersistentProxyObjectFactory[] $factories
     */
    public function __construct(private iterable $factories)
    {
    }

    /**
     * @param class-string<PersistentProxyObjectFactory> $class
     */
    public function create(string $class): PersistentProxyObjectFactory
    {
        foreach ($this->factories as $factory) {
            if ($class === $factory::class) {
                return $factory;
            }
        }

        try {
            return new $class();
        } catch (\ArgumentCountError $e) {
            throw new \RuntimeException('Model Factories with dependencies (Model Factory services) cannot be used without the foundry bundle.', 0, $e);
        }
    }
}
