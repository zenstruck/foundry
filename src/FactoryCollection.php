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
     * @phpstan-param \Closure():iterable<Attributes>|\Closure():iterable<Factory<T>> $items
     */
    private function __construct(public readonly Factory $factory, private \Closure $items)
    {
    }

    /**
     * @phpstan-assert-if-true non-empty-list<Factory<T>> $potentialFactories
     *
     * @internal
     */
    public static function accepts(mixed $potentialFactories): bool
    {
        if (!is_array($potentialFactories) || count($potentialFactories) === 0 || !array_is_list($potentialFactories)) {
            return false;
        }

        if (!$potentialFactories[0] instanceof ObjectFactory) {
            return false;
        }

        foreach ($potentialFactories as $potentialFactory) {
            if (!$potentialFactory instanceof ObjectFactory
                || $potentialFactory::class() !== $potentialFactories[0]::class()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<mixed> $factories
     *
     * @return self<T>
     *
     * @internal
     */
    public static function fromFactoriesList(array $factories): self
    {
        if (!self::accepts($factories)) {
            throw new \InvalidArgumentException('All factories must be of the same type.');
        }

        return new self($factories[0], static fn() => $factories);
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
     * @phpstan-param  iterable<Attributes> $items
     * @return self<T>
     */
    public static function sequence(Factory $factory, iterable $items): self
    {
        return new self($factory, static fn() => $items);
    }

    /**
     * @phpstan-param Attributes $attributes
     *
     * @return list<T>
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
        foreach (($this->items)() as $attributesOrFactory) {
            if ($attributesOrFactory instanceof Factory) {
                $factories[] = $attributesOrFactory;

                continue;
            }

            $factories[] = $this->factory->with($attributesOrFactory)->with(['__index' => $i++]);
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
