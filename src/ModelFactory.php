<?php

namespace Zenstruck\Foundry;

/**
 * @template TModel of object
 * @template-extends Factory<TModel>
 *
 * @method static Proxy[]|TModel[] createMany(int $number, array|callable $attributes = [])
 * @psalm-method static list<Proxy<TModel>> createMany(int $number, array|callable $attributes = [])
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ModelFactory extends Factory
{
    public function __construct()
    {
        parent::__construct(static::getClass());
    }

    public static function __callStatic(string $name, array $arguments)
    {
        if ('createMany' !== $name) {
            throw new \BadMethodCallException(\sprintf('Call to undefined static method "%s::%s".', static::class, $name));
        }

        return static::new()->many($arguments[0])->create($arguments[1] ?? []);
    }

    /**
     * @param array|callable|string $defaultAttributes If string, assumes state
     * @param string                ...$states         Optionally pass default states (these must be methods on your ObjectFactory with no arguments)
     *
     * @return static
     */
    final public static function new($defaultAttributes = [], string ...$states): self
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
            ->withAttributes([$factory, 'getDefaults'])
            ->withAttributes($defaultAttributes)
            ->initialize()
        ;

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
     * @psalm-return Proxy<TModel>
     */
    final public static function createOne(array $attributes = []): Proxy
    {
        return static::new()->create($attributes);
    }

    /**
     * Try and find existing object for the given $attributes. If not found,
     * instantiate and persist.
     *
     * @return Proxy<TModel>&TModel
     * @psalm-return Proxy<TModel>
     */
    final public static function findOrCreate(array $attributes): Proxy
    {
        if ($found = static::repository()->find($attributes)) {
            return \is_array($found) ? $found[0] : $found;
        }

        return static::new()->create($attributes);
    }

    /**
     * @see RepositoryProxy::first()
     *
     * @return Proxy<TModel>&TModel
     * @psalm-return Proxy<TModel>
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
     * @psalm-return Proxy<TModel>
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
     * @psalm-return Proxy<TModel>
     */
    final public static function random(array $attributes = []): Proxy
    {
        return static::repository()->random($attributes);
    }

    /**
     * Fetch one random object and create a new object if none exists.
     *
     * @return Proxy<TModel>&TModel
     * @psalm-return Proxy<TModel>
     */
    final public static function randomOrCreate(array $attributes = []): Proxy
    {
        try {
            return static::repository()->random($attributes);
        } catch (\RuntimeException $e) {
            return static::new()->create($attributes);
        }
    }

    /**
     * @see RepositoryProxy::randomSet()
     *
     * @return list<TModel&Proxy<TModel>>
     * @psalm-return list<Proxy<TModel>>
     */
    final public static function randomSet(int $number, array $attributes = []): array
    {
        return static::repository()->randomSet($number, $attributes);
    }

    /**
     * @see RepositoryProxy::randomRange()
     *
     * @return list<TModel&Proxy<TModel>>
     * @psalm-return list<Proxy<TModel>>
     */
    final public static function randomRange(int $min, int $max, array $attributes = []): array
    {
        return static::repository()->randomRange($min, $max, $attributes);
    }

    /**
     * @see RepositoryProxy::count()
     */
    final public static function count(): int
    {
        return static::repository()->count();
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
     * @psalm-return list<Proxy<TModel>>
     */
    final public static function all(): array
    {
        return static::repository()->findAll();
    }

    /**
     * @see RepositoryProxy::find()
     *
     * @return Proxy<TModel>&TModel
     * @psalm-return Proxy<TModel>
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
     * @psalm-return list<Proxy<TModel>>
     */
    final public static function findBy(array $attributes): array
    {
        return static::repository()->findBy($attributes);
    }

    final public static function assert(): RepositoryAssertions
    {
        return static::repository()->assert();
    }

    /**
     * @psalm-return RepositoryProxy<TModel>
     */
    final public static function repository(): RepositoryProxy
    {
        return static::configuration()->repositoryFor(static::getClass());
    }

    /**
     * @internal
     * @psalm-return class-string<TModel>
     */
    final public static function getEntityClass(): string
    {
        return static::getClass();
    }

    /** @psalm-return class-string<TModel> */
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

    /**
     * @param array|callable $attributes
     *
     * @return static
     */
    final protected function addState($attributes = []): self
    {
        return $this->withAttributes($attributes);
    }

    abstract protected function getDefaults(): array;
}
