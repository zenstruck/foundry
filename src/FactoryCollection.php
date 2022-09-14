<?php

namespace Zenstruck\Foundry;

/**
 * @template TObject of object
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoryCollection implements \IteratorAggregate
{
    /** @var Factory<TObject> */
    private $factory;

    /** @var int */
    private $min;

    /** @var int */
    private $max;

    /** @var iterable<array<string, mixed>>|null */
    private $sequence;

    /**
     * @param int|null                       $max      If set, when created, the collection will be a random size between $min and $max
     * @param iterable<array<string, mixed>> $sequence
     *
     * @psalm-param Factory<TObject> $factory
     *
     * @deprecated using directly FactoryCollection's constructor is deprecated. It will be private in v2. Use named constructors instead.
     */
    public function __construct(Factory $factory, ?int $min = null, ?int $max = null, ?iterable $sequence = null, bool $calledInternally = false)
    {
        if ($max && $min > $max) {
            throw new \InvalidArgumentException('Min must be less than max.');
        }

        if (!$calledInternally) {
            trigger_deprecation('zenstruck/foundry', '1.22.0', "using directly FactoryCollection's constructor is deprecated. It will be private in v2. Use named constructors instead.");
        }

        $this->factory = $factory;
        $this->min = $min;
        $this->max = $max ?? $min;
        $this->sequence = $sequence;
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
     * @param array|callable $attributes
     *
     * @return list<TObject&Proxy<TObject>>
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-return list<Proxy<TObject>>
     */
    public function create($attributes = []): array
    {
        return \array_map(
            static function(Factory $factory) use ($attributes) {
                return $factory->create($attributes);
            },
            $this->all()
        );
    }

    /**
     * @return Factory[]
     *
     * @psalm-return list<Factory<TObject>>
     */
    public function all(): array
    {
        if (!$this->sequence) {
            /** @psalm-suppress TooManyArguments */
            return \array_map(
                function() {
                    return clone $this->factory;
                },
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

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->all());
    }

    public function asDataProvider(): iterable
    {
        foreach ($this as $i => $factory) {
            yield [$factory];
        }
    }
}
