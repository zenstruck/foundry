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
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T
 * @implements \IteratorAggregate<Factory<T>>
 *
 * @phpstan-import-type Attributes from Factory
 */
final class FactoryCollection implements \IteratorAggregate
{
    /**
     * @param Factory<T>                      $factory
     * @param \Closure():iterable<Attributes> $items
     */
    private function __construct(public readonly Factory $factory, private \Closure $items)
    {
    }

    /**
     * @param Factory<T> $factory
     *
     * @return self<T>
     */
    public static function many(Factory $factory, int $count): self
    {
        return new self($factory, static fn() => \array_fill(0, $count, []));
    }

    /**
     * @param Factory<T> $factory
     *
     * @return self<T>
     */
    public static function range(Factory $factory, int $min, int $max): self
    {
        if ($min > $max) {
            throw new \InvalidArgumentException('Min must be less than max.');
        }

        return new self($factory, static fn() => \array_fill(0, \random_int($min, $max), []));
    }

    /**
     * @param  Factory<T>           $factory
     * @param  iterable<Attributes> $items
     * @return self<T>
     */
    public static function sequence(Factory $factory, iterable $items): self
    {
        return new self($factory, static fn() => $items);
    }

    /**
     * @param Attributes $attributes
     *
     * @return T[]
     */
    public function create(array|callable $attributes = []): array
    {
        return \array_map(static fn(Factory $f) => $f->create($attributes), $this->all());
    }

    /**
     * @return list<Factory<T>>
     */
    public function all(): array
    {
        $factories = [];

        $i = 1;
        foreach (($this->items)() as $attributes) {
            $factories[] = $this->factory->with($attributes)->with(['__index' => $i++]);
        }

        return $factories;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * @return iterable<array{Factory<T>}>
     */
    public function asDataProvider(): iterable
    {
        foreach ($this as $factory) {
            yield [$factory];
        }
    }
}
