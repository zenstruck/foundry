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

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\PersistMode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T
 * @template TFactory of Factory<T>
 * @implements \IteratorAggregate<TFactory>
 *
 * @phpstan-import-type Attributes from Factory
 */
final class FactoryCollection implements \IteratorAggregate
{
    private PersistMode $persistMode;

    /**
     * @param TFactory $factory
     * @phpstan-param \Closure():iterable<Attributes>|\Closure():iterable<TFactory> $items
     */
    private function __construct(public readonly Factory $factory, private \Closure $items)
    {
        $this->persistMode = $this->factory instanceof PersistentObjectFactory
            ? $this->factory->persistMode()
            : PersistMode::WITHOUT_PERSISTING;
    }

    /**
     * @internal
     */
    public function withPersistMode(PersistMode $persistMode): static
    {
        $clone = clone $this;
        $clone->persistMode = $persistMode;

        return $clone;
    }

    /**
     * @phpstan-assert-if-true non-empty-list<TFactory> $potentialFactories
     *
     * @internal
     */
    public static function accepts(mixed $potentialFactories): bool
    {
        if (!\is_array($potentialFactories) || 0 === \count($potentialFactories) || !\array_is_list($potentialFactories)) {
            return false;
        }

        if (!$potentialFactories[0] instanceof ObjectFactory) {
            return false;
        }

        foreach ($potentialFactories as $potentialFactory) {
            if (!$potentialFactory instanceof ObjectFactory
                || $potentialFactories[0]::class() !== $potentialFactory::class()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<TFactory> $factories
     *
     * @return self<T, TFactory>
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
     * @param TFactory $factory
     *
     * @return self<T, TFactory>
     */
    public static function many(Factory $factory, int $count): self
    {
        return new self($factory, static fn() => \array_fill(0, $count, []));
    }

    /**
     * @param TFactory $factory
     *
     * @return self<T, TFactory>
     */
    public static function range(Factory $factory, int $min, int $max): self
    {
        if ($min > $max) {
            throw new \InvalidArgumentException('Min must be less than max.');
        }

        return new self($factory, static fn() => \array_fill(0, \random_int($min, $max), []));
    }

    /**
     * @param TFactory $factory
     * @phpstan-param  iterable<Attributes> $items
     * @return self<T, TFactory>
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
     * @return list<TFactory>
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

        return \array_map( // @phpstan-ignore return.type (PHPStan does not understand we have an array of factories)
            function(Factory $f) {
                if ($f instanceof PersistentObjectFactory) {
                    return $f->withPersistMode($this->persistMode);
                }

                return $f;
            },
            $factories
        );
    }

    /**
     * @param list<mixed> $values
     *
     * @return self<T, TFactory>
     */
    public function distribute(string $field, array $values): self
    {
        $factories = $this->all();

        if (\count($factories) !== \count($values)) {
            throw new \InvalidArgumentException('Number of values must match number of factories.');
        }

        return new self(
            $this->factory,
            static fn() => \array_map(
                static fn(Factory $f, $value) => $f->with([$field => $value]),
                $factories,
                $values
            )
        );
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * @return iterable<array{TFactory}>
     */
    public function asDataProvider(): iterable
    {
        foreach ($this as $factory) {
            yield [$factory];
        }
    }
}
