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

/**
 * @template TModel of object
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @deprecated
 *
 * @phpstan-import-type SequenceAttributes from BaseFactory
 */
final class AnonymousFactory implements \Countable, \IteratorAggregate
{
    /**
     * @param class-string<TModel> $class
     */
    public function __construct(private string $class, private array|\Closure $defaultAttributes = [])
    {
        trigger_deprecation(
            'zenstruck\foundry',
            '1.30',
            'Class "AnonymousFactory" is deprecated and will be removed in 2.0. Use the "anonymous()" or "repository()" functions instead.'
        );
    }

    /**
     * @param class-string<TModel> $class
     */
    public static function new(string $class, array|\Closure $defaultAttributes = []): self
    {
        return new self($class, $defaultAttributes);
    }

    /**
     * Try and find existing object for the given $attributes. If not found,
     * instantiate and persist.
     *
     * @return Proxy&TModel
     * @phpstan-return Proxy<TModel>
     */
    public function findOrCreate(array $attributes): Proxy
    {
        if ($found = $this->repository()->find($attributes)) {
            return $found;
        }

        return $this->create($attributes);
    }

    /**
     * @throws \RuntimeException If no entities exist
     * @see RepositoryProxy::first()
     */
    public function first(string $sortedField = 'id'): Proxy
    {
        if (null === $proxy = $this->repository()->first($sortedField)) {
            throw new \RuntimeException(\sprintf('No "%s" objects persisted.', $this->class));
        }

        return $proxy;
    }

    /**
     * @throws \RuntimeException If no entities exist
     * @see RepositoryProxy::last()
     */
    public function last(string $sortedField = 'id'): Proxy
    {
        if (null === $proxy = $this->repository()->last($sortedField)) {
            throw new \RuntimeException(\sprintf('No "%s" objects persisted.', $this->class));
        }

        return $proxy;
    }

    /**
     * @see RepositoryProxy::random()
     *
     * @return Proxy&TModel
     * @phpstan-return Proxy<TModel>
     */
    public function random(array $attributes = []): Proxy
    {
        return $this->repository()->random($attributes);
    }

    /**
     * Fetch one random object and create a new object if none exists.
     *
     * @return Proxy&TModel
     * @phpstan-return Proxy<TModel>
     */
    public function randomOrCreate(array $attributes = []): Proxy
    {
        try {
            return $this->repository()->random($attributes);
        } catch (\RuntimeException) {
            return $this->create($attributes);
        }
    }

    /**
     * @see RepositoryProxy::randomSet()
     *
     * @return list<Proxy&TModel>
     * @phpstan-return list<Proxy<TModel>>
     */
    public function randomSet(int $number, array $attributes = []): array
    {
        return $this->repository()->randomSet($number, $attributes);
    }

    /**
     * @see RepositoryProxy::randomRange()
     *
     * @return list<Proxy&TModel>
     * @phpstan-return list<Proxy<TModel>>
     */
    public function randomRange(int $min, int $max, array $attributes = []): array
    {
        return $this->repository()->randomRange($min, $max, $attributes);
    }

    /**
     * @see RepositoryProxy::count()
     */
    public function count(): int
    {
        return $this->repository()->count();
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * @see RepositoryProxy::truncate()
     */
    public function truncate(): void
    {
        $this->repository()->truncate();
    }

    /**
     * @see RepositoryProxy::findAll()
     *
     * @return list<Proxy<TModel>>
     */
    public function all(): array
    {
        return $this->repository()->findAll();
    }

    /**
     * @return Proxy&TModel
     * @throws \RuntimeException If no entity found
     * @see RepositoryProxy::find()
     *
     * @phpstan-param Proxy<TModel>|array|mixed $criteria
     *
     * @phpstan-return Proxy<TModel>
     */
    public function find($criteria): Proxy
    {
        if (null === $proxy = $this->repository()->find($criteria)) {
            throw new \RuntimeException(\sprintf('Could not find "%s" object.', $this->class));
        }

        return $proxy;
    }

    /**
     * @see RepositoryProxy::findBy()
     *
     * @return list<Proxy&TModel>
     * @phpstan-return list<Proxy<TModel>>
     */
    public function findBy(array $attributes): array
    {
        return $this->repository()->findBy($attributes);
    }

    public function assert(): RepositoryAssertions
    {
        return $this->repository()->assert();
    }

    /**
     * @return RepositoryProxy<TModel>
     */
    public function repository(): RepositoryProxy
    {
        return PersistentObjectFactory::persistenceManager()->repositoryFor($this->class);
    }

    /**
     * @return Proxy&TModel
     * @phpstan-return Proxy<TModel>
     */
    public function create(array|callable $attributes = []): mixed
    {
        $defaultAttributes = \is_callable($this->defaultAttributes) ? ($this->defaultAttributes)() : $this->defaultAttributes;
        $attributes = \is_callable($attributes) ? $attributes() : $attributes;

        return create(
            $this->class,
            \array_merge($defaultAttributes, $attributes)
        );
    }

    /**
     * @return FactoryCollection<TModel>
     */
    public function many(int $min, ?int $max = null): FactoryCollection
    {
        return anonymous($this->class, $this->defaultAttributes)->many($min, $max);
    }

    /**
     * @param  SequenceAttributes        $sequence
     * @return FactoryCollection<TModel>
     */
    public function sequence(iterable|callable $sequence): FactoryCollection
    {
        return anonymous($this->class, $this->defaultAttributes)->sequence($sequence);
    }
}
