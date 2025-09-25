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
 * @phpstan-import-type Parameters from Factory
 */
final class FactoryCollection implements \IteratorAggregate
{
    private PersistMode $persistMode;
    private bool $isRootFactory = true;

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
     * @internal
     * @return self<T, TFactory>
     */
    public function notRootFactory(): static
    {
        $clone = clone $this;
        $clone->isRootFactory = false;

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

        return new self($factory, static fn() => \array_fill(0, \mt_rand($min, $max), []));
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
        $factories = $this->all();

        if (Configuration::instance()->flushOnce && $this->isRootFactory && $this->factory instanceof PersistentObjectFactory && $this->factory->isPersisting()) {
            $lastFactory = \array_pop($factories);
            // @phpstan-ignore method.notFound (phpstan does not understand that we only have persistent factories here)
            $factories = \array_map(static fn(Factory $f) => $f->notRootFactory(), $factories);

            if (null !== $lastFactory) {
                $factories[] = $lastFactory;
            }
        }

        return \array_map(static fn(Factory $f) => $f->create($attributes), $factories);
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
                    if (!$this->isRootFactory) {
                        $f = $f->notRootFactory();
                    }

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

    /**
     * @internal
     */
    public function reuse(object ...$objects): static
    {
        if (0 === \count($objects)) {
            return $this;
        }

        $factories = $this->all();

        return new self(
            $this->factory,
            static fn() => \array_map(
                static fn(Factory $f) => $f instanceof ObjectFactory ? $f->reuse(...$objects) : $f,
                $factories,
            )
        );
    }

    /**
     * @phpstan-param callable(int):mixed[] $attributes
     */
    public function applyStateMethod(string $stateMethodName, ?callable $attributes = null): static
    {
        $attributes ??= fn(int $index) => [];

        try {
            $refectionMethod = new \ReflectionMethod($this->factory, $stateMethodName);
        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException(\sprintf('State method "%s" does not exist on factory "%s".', $stateMethodName, $this->factory::class), previous: $e);
        }

        if ($refectionMethod->isStatic()) {
            throw new \InvalidArgumentException(\sprintf('Method "%s::%s()" is static and cannot be used as a state method.', $this->factory::class, $stateMethodName));
        }

        $factories = $this->all();

        $stateMethodNumberOfRequiredParameters = $refectionMethod->getNumberOfRequiredParameters();
        $stateMethodNumberOfParameters = $refectionMethod->getNumberOfParameters();
        $stateMethodParameterNames = \array_map(static fn(\ReflectionParameter $p) => $p->getName(), $refectionMethod->getParameters());

        return new self(
            $this->factory,
            static fn() => \array_map(
                static function(Factory $f, int $index) use ($stateMethodName, $attributes, $stateMethodNumberOfRequiredParameters, $stateMethodNumberOfParameters, $stateMethodParameterNames) {
                    $parameters = $attributes($index + 1);

                    if (\count($parameters) < $stateMethodNumberOfRequiredParameters || \count($parameters) > $stateMethodNumberOfParameters) {
                        throw new \InvalidArgumentException(\sprintf('Invalid number of parameters for state method "%s::%s()".', $f::class, $stateMethodName));
                    }

                    if (!\array_is_list($parameters) && $extraParameters = \array_diff(\array_keys($parameters), $stateMethodParameterNames)) {
                        throw new \InvalidArgumentException(\sprintf('Parameter(s) "%s" don\'t exist for state method "%s::%s()".', \implode(',', $extraParameters), $f::class, $stateMethodName));
                    }

                    $newFactory = $f->{$stateMethodName}(...$parameters);

                    if ($newFactory::class !== $f::class) {
                        throw new \InvalidArgumentException(\sprintf('State method "%s::%s()" does not return a "%1$s".', $f::class, $stateMethodName));
                    }

                    return $newFactory;
                },
                $factories,
                \array_keys($factories),
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
