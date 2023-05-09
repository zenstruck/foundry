<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Object;

use Zenstruck\Foundry\BaseFactory;
use Zenstruck\Foundry\Instantiator;
use Zenstruck\Foundry\LazyValue;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of object
 * @extends BaseFactory<T>
 *
 * @phpstan-type CallableInstantiator Instantiator<T>|\Closure(Parameters,class-string<T>):T
 *
 * @phpstan-import-type Parameters from BaseFactory
 * @phpstan-import-type Attributes  from BaseFactory
 */
abstract class ObjectFactory extends BaseFactory
{
    /** @var CallableInstantiator */
    private \Closure|Instantiator $instantiator;

    /** @var array<callable(Parameters,class-string<T>):Parameters> */
    private array $beforeInstantiate = [];

    /** @var array<callable(T,Parameters):void> */
    private array $afterInstantiate = [];

    /**
     * @return class-string<T>
     */
    abstract public static function class(): string;

    public function create(array|callable $attributes = []): object
    {
        return $this->normalizeAndInstantiate($attributes)[0];
    }

    /**
     * @param CallableInstantiator $instantiator
     */
    final public function instantiateWith(callable $instantiator): static
    {
        $clone = clone $this;
        $clone->instantiator = $instantiator;

        return $clone;
    }

    /**
     * @param callable(Parameters,class-string<T>):Parameters $callback
     */
    final public function beforeInstantiate(callable $callback): static
    {
        $clone = clone $this;
        $clone->beforeInstantiate[] = $callback;

        return $clone;
    }

    /**
     * @param callable(T,Parameters):void $callback
     */
    final public function afterInstantiate(callable $callback): static
    {
        $clone = clone $this;
        $clone->afterInstantiate[] = $callback;

        return $clone;
    }

    /**
     * @internal
     * @param Attributes $attributes
     *
     * @return array{0:T, 1:Parameters}
     */
    protected function normalizeAndInstantiate(array|callable $attributes = []): array
    {
        $parameters = [];
        foreach ($this->mergedAttributes($attributes) as $name => $attribute) {
            $parameters[$name] = $this->normalizeAttribute($attribute, $name);
        }

        return $this->instantiate($parameters);
    }

    /**
     * @internal
     * @param Parameters $parameters
     *
     * @return array{0:T, 1:Parameters}
     */
    final protected function instantiate(array $parameters): array
    {
        // execute "lazy" values
        $parameters = LazyValue::normalizeArray($parameters);

        foreach ($this->beforeInstantiate as $hook) {
            $parameters = $hook($parameters, static::class());

            if (!\is_array($parameters)) {
                throw new \LogicException('Before Instantiate event callback must return an array.');
            }
        }

        $object = ($this->instantiator ?? self::factoryManager()->defaultObjectInstantiator())($parameters, static::class());

        foreach ($this->afterInstantiate as $hook) {
            $hook($object, $parameters);
        }

        return [$object, $parameters];
    }
}
