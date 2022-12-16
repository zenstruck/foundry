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
 * @template TObject of object
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryCollection implements \IteratorAggregate
{
    private ?int $min;

    private ?int $max;

    /**
     * @param int|null                       $max           If set, when created, the collection will be a random size between $min and $max
     * @param iterable<array<string, mixed>> $sequence|null $sequence
     *
     * @phpstan-param Factory<TObject> $factory
     *
     * @param Factory<object> $factory
     *
     *@deprecated using directly FactoryCollection's constructor is deprecated. It will be private in v2. Use named constructors instead.
     */
    public function __construct(private Factory $factory, ?int $min = null, ?int $max = null, private ?iterable $sequence = null, bool $calledInternally = false)
    {
        if ($max && $min > $max) {
            throw new \InvalidArgumentException('Min must be less than max.');
        }

        if (!$calledInternally) {
            trigger_deprecation('zenstruck/foundry', '1.22.0', "using directly FactoryCollection's constructor is deprecated. It will be private in v2. Use named constructors instead.");
        }

        $this->min = $min;
        $this->max = $max ?? $min;
    }

    public static function set(Factory $factory, int $count): self
    {
        return new self($factory, $count, null, null, true);
    }

    public static function range(Factory $factory, int $min, int $max): self
    {
        return new self($factory, $min, $max, null, true);
    }

    /**
     * @param iterable<array<string, mixed>> $sequence
     */
    public static function sequence(Factory $factory, iterable $sequence): self
    {
        return new self($factory, 0, null, $sequence, true);
    }

    /**
     * @return list<TObject&Proxy<TObject>>
     *
     * @phpstan-return list<Proxy<TObject>>
     */
    public function create(array|callable $attributes = []): array
    {
        $objects = [];
        foreach ($this->all() as $i => $factory) {
            $objects[] = $factory->create(
                \is_callable($attributes) ? $attributes($i + 1) : $attributes
            );
        }

        return $objects;
    }

    /**
     * @return Factory[]
     *
     * @phpstan-return list<Factory<TObject>>
     */
    public function all(): array
    {
        if (!$this->sequence) {
            return \array_map(
                fn(): Factory => clone $this->factory,
                \array_fill(0, \random_int($this->min, $this->max), null)
            );
        }

        $factories = [];
        foreach ($this->sequence as $attributes) {
            $factories[] = (clone $this->factory)->withAttributes($attributes);
        }

        return $factories;
    }

    public function factory(): Factory
    {
        return $this->factory;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * @return \Iterator<mixed[]>
     */
    public function asDataProvider(): iterable
    {
        foreach ($this as $factory) {
            yield [$factory];
        }
    }
}
