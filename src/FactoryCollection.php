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
 * @template T
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type Parameters from BaseFactory
 * @phpstan-import-type Attributes  from BaseFactory
 */
final class FactoryCollection implements \IteratorAggregate
{
    private ?int $min;

    private ?int $max;

    /**
     * @param int|null        $max      If set, when created, the collection will be a random size between $min and $max
     * @param Parameters|null $sequence
     *
     * @deprecated using directly FactoryCollection's constructor is deprecated. It will be private in v2. Use named constructors instead.
     */
    public function __construct(private BaseFactory $factory, ?int $min = null, ?int $max = null, private ?iterable $sequence = null, bool $calledInternally = false)
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

    public static function set(BaseFactory $factory, int $count): self
    {
        return new self($factory, $count, null, null, true);
    }

    public static function range(BaseFactory $factory, int $min, int $max): self
    {
        return new self($factory, $min, $max, null, true);
    }

    /**
     * @param Parameters $sequence
     */
    public static function sequence(BaseFactory $factory, iterable $sequence): self
    {
        return new self($factory, 0, null, $sequence, true);
    }

    /**
     * @param Attributes $attributes
     *
     * @return list<T>
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
     * @return list<BaseFactory>
     */
    public function all(): array
    {
        if (!$this->sequence) {
            return \array_map(
                fn(): BaseFactory => clone $this->factory,
                \array_fill(0, \random_int($this->min, $this->max), null)
            );
        }

        $factories = [];
        foreach ($this->sequence as $attributes) {
            $factories[] = (clone $this->factory)->withAttributes($attributes);
        }

        return $factories;
    }

    public function factory(): BaseFactory
    {
        return $this->factory;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * @return \Iterator<list<BaseFactory>>
     */
    public function asDataProvider(): iterable
    {
        foreach ($this as $factory) {
            yield [$factory];
        }
    }
}
