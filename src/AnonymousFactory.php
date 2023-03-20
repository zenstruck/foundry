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
 * @template TModel of object
 * @template-extends Factory<TModel>
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @deprecated
 */
final class AnonymousFactory extends Factory implements \Countable, \IteratorAggregate
{
    public function __construct(string $class, array|callable $defaultAttributes = [])
    {
        trigger_deprecation('zenstruck\foundry', '1.30', 'Class "AnonymousFactory" is deprecated and will be removed in 2.0. Use the "anonymous()" or "repository()" functions instead.');

        parent::__construct($class, $defaultAttributes);
    }

    /**
     * @see Factory::__construct()
     *
     * @param class-string<TModel> $class
     */
    public static function new(string $class, array|callable $defaultAttributes = []): self
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
     * @see RepositoryProxy::first()
     *
     * @throws \RuntimeException If no entities exist
     */
    public function first(string $sortedField = 'id'): Proxy
    {
        if (null === $proxy = $this->repository()->first($sortedField)) {
            throw new \RuntimeException(\sprintf('No "%s" objects persisted.', $this->class()));
        }

        return $proxy;
    }

    /**
     * @see RepositoryProxy::last()
     *
     * @throws \RuntimeException If no entities exist
     */
    public function last(string $sortedField = 'id'): Proxy
    {
        if (null === $proxy = $this->repository()->last($sortedField)) {
            throw new \RuntimeException(\sprintf('No "%s" objects persisted.', $this->class()));
        }

        return $proxy;
    }

    /**
     * @see RepositoryProxy::random()
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
     * @return object[]
     */
    public function randomSet(int $number, array $attributes = []): array
    {
        return $this->repository()->randomSet($number, $attributes);
    }

    /**
     * @see RepositoryProxy::randomRange()
     *
     * @return object[]
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
     * @return object[]
     */
    public function all(): array
    {
        return $this->repository()->findAll();
    }

    /**
     * @see RepositoryProxy::find()
     *
     * @phpstan-param Proxy<TModel>|array|mixed $criteria
     *
     * @throws \RuntimeException If no entity found
     */
    public function find($criteria): Proxy
    {
        if (null === $proxy = $this->repository()->find($criteria)) {
            throw new \RuntimeException(\sprintf('Could not find "%s" object.', $this->class()));
        }

        return $proxy;
    }

    /**
     * @see RepositoryProxy::findBy()
     *
     * @return object[]
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
     * @phpstan-return RepositoryProxy<TModel>
     */
    public function repository(): RepositoryProxy
    {
        return self::configuration()->repositoryFor($this->class());
    }
}
