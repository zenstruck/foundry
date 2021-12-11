<?php

namespace Zenstruck\Foundry;

/**
 * @template TModel of object
 * @template-extends Factory<TModel>
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AnonymousFactory extends Factory implements \Countable, \IteratorAggregate
{
    /**
     * @see Factory::__construct()
     */
    public static function new(string $class, $defaultAttributes = []): self
    {
        return new self($class, $defaultAttributes);
    }

    /**
     * Try and find existing object for the given $attributes. If not found,
     * instantiate and persist.
     *
     * @return Proxy&TModel
     * @psalm-return Proxy<TModel>
     */
    public function findOrCreate(array $attributes): Proxy
    {
        if ($found = $this->repository()->find($attributes)) {
            return \is_array($found) ? $found[0] : $found;
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
     * @psalm-return Proxy<TModel>
     */
    public function randomOrCreate(array $attributes = []): Proxy
    {
        try {
            return $this->repository()->random($attributes);
        } catch (\RuntimeException $e) {
            return $this->create($attributes);
        }
    }

    /**
     * @see RepositoryProxy::randomSet()
     */
    public function randomSet(int $number, array $attributes = []): array
    {
        return $this->repository()->randomSet($number, $attributes);
    }

    /**
     * @see RepositoryProxy::randomRange()
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

    public function getIterator(): \Traversable
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
     */
    public function all(): array
    {
        return $this->repository()->findAll();
    }

    /**
     * @see RepositoryProxy::find()
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
     * @psalm-return RepositoryProxy<TModel>
     */
    public function repository(): RepositoryProxy
    {
        return self::configuration()->repositoryFor($this->class());
    }
}
