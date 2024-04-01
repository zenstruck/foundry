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
    public function __construct(public Factory $factory, ?int $min = null, ?int $max = null, private ?iterable $sequence = null, bool $calledInternally = false)
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

    /**
     * @deprecated Use FactoryCollection::many() instead
     */
    public static function set(Factory $factory, int $count): self
    {
        trigger_deprecation('zenstruck/foundry', '1.38.0', 'Method %s() is deprecated and will be removed in 2.0. Use "%s::many()" instead.', __METHOD__, __CLASS__);

        return self::many($factory, $count);
    }

    public static function many(Factory $factory, int $count): self
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
     * @return ($noProxy is true ? list<TObject> : list<Proxy<TObject>>)
     */
    public function create(
        array|callable $attributes = [],
        /**
         * @deprecated
         * @internal
         */
        bool $noProxy = false,
    ): array {
        if (2 === \count(\func_get_args()) && !\str_starts_with(\debug_backtrace(options: \DEBUG_BACKTRACE_IGNORE_ARGS, limit: 1)[0]['class'] ?? '', 'Zenstruck\Foundry')) {
            trigger_deprecation('zenstruck\foundry', '1.38.0', 'Parameter "$noProxy" of method "%s()" is deprecated and will be removed in Foundry 2.0.', __METHOD__);
        }

        $objects = [];
        foreach ($this->all() as $i => $factory) {
            $objects[] = $factory->create(
                \is_callable($attributes) ? $attributes($i + 1) : $attributes,
                $noProxy || !$factory->shouldUseProxy(),
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
                \array_fill(0, \random_int($this->min, $this->max), null),
            );
        }

        $factories = [];
        foreach ($this->sequence as $attributes) {
            $factories[] = (clone $this->factory)->with($attributes);
        }

        return $factories;
    }

    public function factory(): Factory
    {
        trigger_deprecation('zenstruck\foundry', '1.38.0', 'Method "%s()" is deprecated and will be removed in Foundry 2.0. Use public property %s::$factory instead', __METHOD__, __CLASS__);

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
