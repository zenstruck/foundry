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

use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\ProxyGenerator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of object
 * @extends Factory<T>
 *
 * @phpstan-type InstantiatorCallable = Instantiator|callable(Parameters,class-string<T>):T
 * @phpstan-import-type Parameters from Factory
 */
abstract class ObjectFactory extends Factory
{
    /** @phpstan-var list<callable(Parameters, class-string<T>, static):Parameters> */
    private array $beforeInstantiate = [];

    /** @phpstan-var list<callable(T, Parameters, static):void> */
    private array $afterInstantiate = [];

    /** @phpstan-var InstantiatorCallable|null */
    private $instantiator;

    /** @var array<class-string, object> */
    private array $reusedObjects = [];

    /**
     * @return class-string<T>
     */
    abstract public static function class(): string;

    /**
     * @return T
     */
    public function create(callable|array $attributes = []): object
    {
        $parameters = $this->normalizeAttributes($attributes);

        foreach ($this->beforeInstantiate as $hook) {
            $parameters = $hook($parameters, static::class(), $this);

            if (!\is_array($parameters)) {
                throw new \LogicException('Before Instantiate hook callback must return a parameter array.');
            }
        }

        $parameters = $this->normalizeParameters($parameters);
        $instantiator = $this->instantiator ?? Configuration::instance()->instantiator;
        /** @var T $object */
        $object = $instantiator($parameters, static::class());

        foreach ($this->afterInstantiate as $hook) {
            $hook($object, $parameters, $this);
        }

        return $object;
    }

    /**
     * @phpstan-param InstantiatorCallable $instantiator
     *
     * @phpstan-return static
     * @psalm-return static<T>
     */
    final public function instantiateWith(callable $instantiator): static
    {
        $clone = clone $this;
        $clone->instantiator = $instantiator;

        return $clone;
    }

    /**
     * @phpstan-param callable(Parameters, class-string<T>, static):Parameters $callback
     */
    final public function beforeInstantiate(callable $callback): static
    {
        $clone = clone $this;
        $clone->beforeInstantiate[] = $callback;

        return $clone;
    }

    /**
     * @final
     *
     * @phpstan-param callable(T, Parameters, static):void $callback
     */
    public function afterInstantiate(callable $callback): static
    {
        $clone = clone $this;
        $clone->afterInstantiate[] = $callback;

        return $clone;
    }

    /**
     * @phpstan-return static
     * @psalm-return static<T>
     */
    final public function reuse(object ...$objects): static
    {
        if (0 === \count($objects)) {
            return $this;
        }

        $clone = clone $this;

        foreach ($objects as $object) {
            $object = ProxyGenerator::unwrap($object, withAutoRefresh: false);

            if ($object instanceof Factory) {
                throw new \InvalidArgumentException('Cannot reuse a factory.');
            }

            $clone->reusedObjects[$object::class] = $object;
        }

        return $clone;
    }

    protected function normalizeParameter(string $field, mixed $value): mixed
    {
        if ($value instanceof self) {
            // propagate "reused" objects
            foreach ($this->reusedObjects as $item) {
                // "reused" item in the target factory have priority, if they are of the same type
                if (!isset($value->reusedObjects[$item::class])) {
                    $value = $value->reuse($item);
                }
            }
        }

        return parent::normalizeParameter($field, $value);
    }

    /**
     * @internal
     * @phpstan-return Parameters
     */
    final protected function normalizeReusedAttributes(): array
    {
        if ([] === $this->reusedObjects) {
            return [];
        }

        $attributes = [];

        $properties = (new \ReflectionClass(static::class()))->getProperties();

        foreach ($properties as $property) {
            $type = $property->getType();

            if (null === $type) {
                continue;
            }

            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            if (isset($this->reusedObjects[$type->getName()])) {
                $attributes[$property->getName()] = $this->reusedObjects[$type->getName()];

                continue;
            }

            // test if reused object is a subclass of the property's type
            foreach ($this->reusedObjects as $reusedObject) {
                if (\is_a($reusedObject, $type->getName())) {
                    $attributes[$property->getName()] = $reusedObject;

                    break;
                }
            }
        }

        return $attributes;
    }

    /**
     * @return list<object>
     * @internal
     */
    final protected function reusedObjects(): array
    {
        return \array_values($this->reusedObjects);
    }
}
