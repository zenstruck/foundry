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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 *
 * @template T
 * @phpstan-type Parameters = array<string,mixed>
 * @phpstan-type Attributes = Parameters|callable(int):Parameters
 * @phpstan-type Sequence = iterable<Parameters>|callable(): iterable<Parameters>
 */
abstract class Factory
{
    /** @phpstan-var Attributes[] */
    private array $attributes;

    // keep an empty constructor for BC
    public function __construct()
    {
    }

    /**
     * @phpstan-return static
     * @phpstan-param Attributes $attributes
     */
    final public static function new(array|callable $attributes = []): static
    {
        if (Configuration::isBooted()) {
            $factory = Configuration::instance()->factories->get(static::class);
        }

        try {
            $factory ??= new static(); // @phpstan-ignore new.static
        } catch (\ArgumentCountError $e) {
            throw new \LogicException('Factories with dependencies (services) cannot be created before foundry is booted.', previous: $e);
        }

        return $factory
            ->initializeInternal()
            ->initialize()
            ->with($attributes);
    }

    /**
     * @phpstan-param Attributes $attributes
     *
     * @return T
     */
    public static function createOne(array|callable $attributes = []): mixed
    {
        return static::new()->create($attributes);
    }

    /**
     * @phpstan-param Attributes $attributes
     *
     * @return list<T>
     */
    final public static function createMany(int $number, array|callable $attributes = []): array
    {
        return static::new()->many($number)->create($attributes);
    }

    /**
     * @phpstan-param Attributes $attributes
     *
     * @return list<T>
     */
    final public static function createRange(int $min, int $max, array|callable $attributes = []): array
    {
        return static::new()->range($min, $max)->create($attributes);
    }

    /**
     * @phpstan-param Sequence $sequence
     *
     * @return list<T>
     */
    final public static function createSequence(iterable|callable $sequence): array
    {
        return static::new()->sequence($sequence)->create();
    }

    /**
     * @phpstan-param Attributes $attributes
     *
     * @return T
     */
    abstract public function create(array|callable $attributes = []): mixed;

    /**
     * @return FactoryCollection<T, static>
     */
    final public function many(int $count): FactoryCollection
    {
        return FactoryCollection::many($this, $count); // @phpstan-ignore return.type
    }

    /**
     * @return FactoryCollection<T, static>
     */
    final public function range(int $min, int $max): FactoryCollection
    {
        return FactoryCollection::range($this, $min, $max); // @phpstan-ignore return.type
    }

    /**
     * @phpstan-param Sequence $sequence
     * @return FactoryCollection<T, static>
     */
    final public function sequence(iterable|callable $sequence): FactoryCollection
    {
        if (\is_callable($sequence)) {
            $sequence = $sequence();
        }

        return FactoryCollection::sequence($this, $sequence); // @phpstan-ignore return.type
    }

    /**
     * @phpstan-param Attributes $attributes
     *
     * @psalm-return static<T>
     * @phpstan-return static
     */
    final public function with(array|callable $attributes = []): static
    {
        $clone = clone $this;
        $clone->attributes[] = $attributes;

        return $clone;
    }

    final protected static function faker(): Faker\Generator
    {
        return Configuration::instance()->faker;
    }

    /**
     * Override to adjust default attributes & config.
     */
    protected function initialize(): static
    {
        return $this;
    }

    /**
     * @internal
     *
     * @phpstan-param Attributes $attributes
     *
     * @phpstan-return Parameters
     */
    final protected function normalizeAttributes(array|callable $attributes = []): array
    {
        $attributes = [$this->defaults(), ...$this->attributes, $attributes];
        $index = 1;

        // find if an index was set by factory collection
        foreach ($attributes as $i => $attr) {
            if (\is_array($attr) && isset($attr['__index'])) {
                $index = $attr['__index'];
                unset($attributes[$i]);
                break;
            }
        }

        return \array_merge(
            ...\array_map(static fn(array|callable $attr) => \is_callable($attr) ? $attr($index) : $attr, $attributes)
        );
    }

    /**
     * @internal
     */
    protected function initializeInternal(): static
    {
        return $this;
    }

    /**
     * @internal
     *
     * @phpstan-param Parameters $parameters
     *
     * @phpstan-return Parameters
     */
    protected function normalizeParameters(array $parameters): array
    {
        return \array_combine(
            \array_keys($parameters),
            \array_map($this->normalizeParameter(...), \array_keys($parameters), $parameters)
        );
    }

    /**
     * @internal
     */
    protected function normalizeParameter(string $field, mixed $value): mixed
    {
        if ($value instanceof LazyValue) {
            $value = $value();
        }

        if ($value instanceof self) {
            $value = $value->create();
        }

        if (FactoryCollection::accepts($value)) {
            $value = FactoryCollection::fromFactoriesList($value);
        }

        if ($value instanceof FactoryCollection) {
            $value = $this->normalizeCollection($field, $value);
        }

        if (\is_array($value)) {
            return \array_combine(
                \array_keys($value),
                \array_map($this->normalizeParameter(...), \array_fill(0, \count($value), $field), $value)
            );
        }

        return \is_object($value) ? $this->normalizeObject($value) : $value;
    }

    /**
     * @internal
     *
     * @param FactoryCollection<mixed, Factory<mixed>> $collection
     *
     * @return self<mixed>[]
     */
    protected function normalizeCollection(string $field, FactoryCollection $collection): array
    {
        return \array_map(fn(Factory $f) => $this->normalizeParameter($field, $f), $collection->all());
    }

    /**
     * @internal
     */
    protected function normalizeObject(object $object): object
    {
        return $object;
    }

    /**
     * @phpstan-return Attributes
     */
    abstract protected function defaults(): array|callable;
}
