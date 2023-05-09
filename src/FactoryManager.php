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

use Faker;
use Zenstruck\Foundry\Object\ObjectFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type CallableInstantiator from ObjectFactory
 */
final class FactoryManager
{
    private iterable $factories;

    /** @var CallableInstantiator */
    private Instantiator|\Closure $instantiator;
    private Faker\Generator $faker;

    /**
     * @param BaseFactory[]        $factories
     * @param CallableInstantiator $instantiator
     */
    public function __construct(iterable $factories = [], Instantiator|\Closure $instantiator = null, ?Faker\Generator $faker = null)
    {
        $this->factories = $factories;
        $this->instantiator = $instantiator ?? new Instantiator(); // @phpstan-ignore-line
        $this->faker = $faker ?? Faker\Factory::create();
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
                return $factory;
            }
        }

        try {
            return new $class();
        } catch (\ArgumentCountError $e) {
            throw new \RuntimeException('Factories with dependencies (Factory services) cannot be used without the foundry bundle.', 0, $e);
        }
    }

    /**
     * @return CallableInstantiator
     */
    public function defaultObjectInstantiator(): Instantiator|\Closure
    {
        return $this->instantiator;
    }

    public function faker(): Faker\Generator
    {
        return $this->faker;
    }
}
