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

use Zenstruck\Foundry\Exception\FoundryBootException;

/**
 * @template TModel of object
 * @template-extends Factory<TModel>
 *
 * @method static Proxy[]|TModel[] createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<TModel>> createMany(int $number, array|callable $attributes = [])
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ModelFactory extends Factory
{
    public function __construct()
    {
        parent::__construct(static::getClass());
    }

    /**
     * @phpstan-return list<Proxy<TModel>>
     */
    public static function __callStatic(string $name, array $arguments): array
    {
        if ('createMany' !== $name) {
            throw new \BadMethodCallException(\sprintf('Call to undefined static method "%s::%s".', static::class, $name));
        }

        return static::new()->many($arguments[0])->create($arguments[1] ?? []);
    }

    /**
     * @param array|callable|string $defaultAttributes If string, assumes state
     * @param string                ...$states         Optionally pass default states (these must be methods on your ObjectFactory with no arguments)
     */
    final public static function new(array|callable|string $defaultAttributes = [], string ...$states): static
    {
        if (\is_string($defaultAttributes)) {
            $states = \array_merge([$defaultAttributes], $states);
            $defaultAttributes = [];
        }

        try {
            $factory = self::isBooted() ? self::configuration()->factories()->create(static::class) : new static();
        } catch (\ArgumentCountError $e) {
            throw new \RuntimeException('Model Factories with dependencies (Model Factory services) cannot be created before foundry is booted.', 0, $e);
        }

        $factory = $factory
            ->withAttributes(static fn(): array => $factory->getDefaults())
            ->withAttributes($defaultAttributes);

        try {
            if (!Factory::configuration()->isPersistEnabled()) {
                $factory = $factory->withoutPersisting();
            }
        } catch (FoundryBootException) {
        }

        $factory = $factory->initialize();

        if (!$factory instanceof static) {
            throw new \TypeError(\sprintf('"%1$s::initialize()" must return an instance of "%1$s".', static::class));
        }

        foreach ($states as $state) {
            $factory = $factory->{$state}();
        }

        return $factory;
    }

    /**
     * A shortcut to create a single model without states.
     *
     * @return Proxy<TModel>&TModel
     * @phpstan-return Proxy<TModel>
     */
    final public static function createOne(array $attributes = []): Proxy
    {
        return static::new()->create($attributes);
    }

    /**
     * A shortcut to create multiple models, based on a sequence, without states.
     *
     * @param iterable<array<string, mixed>>|callable(): iterable<array<string, mixed>> $sequence
     *
     * @return list<TModel&Proxy<TModel>>
     * @phpstan-return list<Proxy<TModel>>
     */
    final public static function createSequence(iterable|callable $sequence): array
    {
        return static::new()->sequence($sequence)->create();
    }

    /**
     * Try and find existing object for the given $attributes. If not found,
     * instantiate and persist.
     *
     * @return Proxy<TModel>&TModel
     * @phpstan-return Proxy<TModel>
     */
    final public static function findOrCreate(array $attributes): Proxy
    {
        try {
            if ($found = static::repository()->find($attributes)) {
                return $found;
            }
        } catch (FoundryBootException) {
        }

        return static::new()->create($attributes);
    }

    /**
     * @see RepositoryProxy::first()
     *
     * @return Proxy<TModel>&TModel
     * @phpstan-return Proxy<TModel>
     *
     * @throws \RuntimeException If no entities exist
     */
    final public static function first(string $sortedField = 'id'): Proxy
    {
        if (null === $proxy = static::repository()->first($sortedField)) {
            throw new \RuntimeException(\sprintf('No "%s" objects persisted.', static::getClass()));
        }

        return $proxy;
    }

    /**
     * @see RepositoryProxy::last()
     *
     * @return Proxy<TModel>&TModel
     * @phpstan-return Proxy<TModel>
     *
     * @throws \RuntimeException If no entities exist
     */
    final public static function last(string $sortedField = 'id'): Proxy
    {
        if (null === $proxy = static::repository()->last($sortedField)) {
            throw new \RuntimeException(\sprintf('No "%s" objects persisted.', static::getClass()));
        }

        return $proxy;
    }

    /**
     * @see RepositoryProxy::random()
     *
     * @return Proxy<TModel>&TModel
     * @phpstan-return Proxy<TModel>
     */
    final public static function random(array $attributes = []): Proxy
    {
        return static::repository()->random($attributes);
    }

    /**
     * Fetch one random object and create a new object if none exists.
     *
     * @return Proxy<TModel>&TModel
     * @phpstan-return Proxy<TModel>
     */
    final public static function randomOrCreate(array $attributes = []): Proxy
    {
        try {
            return static::repository()->random($attributes);
        } catch (\RuntimeException) {
            return static::new()->create($attributes);
        }
    }

    /**
     * @see RepositoryProxy::randomSet()
     *
     * @return list<TModel&Proxy<TModel>>
     * @phpstan-return list<Proxy<TModel>>
     */
    final public static function randomSet(int $number, array $attributes = []): array
    {
        return static::repository()->randomSet($number, $attributes);
    }

    /**
     * @see RepositoryProxy::randomRange()
     *
     * @return list<TModel&Proxy<TModel>>
     * @phpstan-return list<Proxy<TModel>>
     */
    final public static function randomRange(int $min, int $max, array $attributes = []): array
    {
        return static::repository()->randomRange($min, $max, $attributes);
    }

    /**
     * @see RepositoryProxy::count()
     */
    final public static function count(array $criteria = []): int
    {
        return static::repository()->count($criteria);
    }

    /**
     * @see RepositoryProxy::truncate()
     */
    final public static function truncate(): void
    {
        static::repository()->truncate();
    }

    /**
     * @see RepositoryProxy::findAll()
     *
     * @return list<TModel&Proxy<TModel>>
     * @phpstan-return list<Proxy<TModel>>
     */
    final public static function all(): array
    {
        return static::repository()->findAll();
    }

    /**
     * @see RepositoryProxy::find()
     *
     * @phpstan-param Proxy<TModel>|array|mixed $criteria
     * @phpstan-return Proxy<TModel>
     *
     * @return Proxy<TModel>&TModel
     *
     * @throws \RuntimeException If no entity found
     */
    final public static function find($criteria): Proxy
    {
        if (null === $proxy = static::repository()->find($criteria)) {
            throw new \RuntimeException(\sprintf('Could not find "%s" object.', static::getClass()));
        }

        return $proxy;
    }

    /**
     * @see RepositoryProxy::findBy()
     *
     * @return list<TModel&Proxy<TModel>>
     * @phpstan-return list<Proxy<TModel>>
     */
    final public static function findBy(array $attributes): array
    {
        return static::repository()->findBy($attributes);
    }

    final public static function assert(): RepositoryAssertions
    {
        try {
            return static::repository()->assert();
        } catch (\Throwable $e) {
            throw new \RuntimeException(\sprintf('Cannot create repository assertion: %s', $e->getMessage()), previous: $e);
        }
    }

    /**
     * @phpstan-return RepositoryProxy<TModel>
     */
    final public static function repository(): RepositoryProxy
    {
        return static::configuration()->repositoryFor(static::getClass());
    }

    /**
     * @internal
     * @phpstan-return class-string<TModel>
     */
    final public static function getEntityClass(): string
    {
        return static::getClass();
    }

    /** @phpstan-return class-string<TModel> */
    abstract protected static function getClass(): string;

    /**
     * Override to add default instantiator and default afterInstantiate/afterPersist events.
     *
     * @return static
     */
    protected function initialize()
    {
        return $this;
    }

    final protected function addState(array|callable $attributes = []): static
    {
        return $this->withAttributes($attributes);
    }

    /**
     * @return mixed[]
     */
    abstract protected function getDefaults(): array;
}
