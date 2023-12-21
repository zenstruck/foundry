<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence;

use Zenstruck\Foundry\Exception\FoundryBootException;
use Zenstruck\Foundry\Factory;

/**
 * @template TModel of object
 * @template-extends Factory<TModel>
 *
 * @method static TModel[] createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<TModel> createMany(int $number, array|callable $attributes = [])
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class PersistentObjectFactory extends Factory
{
    public function __construct()
    {
        parent::__construct(static::class());
    }

    /**
     * @phpstan-return list<TModel>
     */
    public static function __callStatic(string $name, array $arguments): array
    {
        if ('createMany' !== $name) {
            throw new \BadMethodCallException(\sprintf('Call to undefined static method "%s::%s".', static::class, $name));
        }

        return static::new()->many($arguments[0])->create($arguments[1] ?? [], noProxy: true);
    }

    /**
     * @final
     *
     * @param array|callable|string $defaultAttributes If string, assumes state
     * @param string                ...$states         Optionally pass default states (these must be methods on your ObjectFactory with no arguments)
     */
    public static function new(array|callable|string $defaultAttributes = [], string ...$states): static
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
            ->with(static fn(): array|callable => $factory->defaults())
            ->with($defaultAttributes);

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
     * @final
     *
     * @return TModel
     */
    public function create(
        array|callable $attributes = [],
        /**
         * @deprecated
         * @internal
         */
        bool $noProxy = false
    ): object {
        if (2 === \count(\func_get_args()) && !\str_starts_with(\debug_backtrace(options: \DEBUG_BACKTRACE_IGNORE_ARGS, limit: 1)[0]['class'] ?? '', 'Zenstruck\Foundry')) {
            trigger_deprecation('zenstruck\foundry', '1.37.0', 'Parameter "$noProxy" of method "%s()" is deprecated and will be removed in Foundry 2.0.', __METHOD__);
        }

        return parent::create(
            $attributes,
            noProxy: true
        );
    }

    /**
     * @final
     *
     * A shortcut to create a single model without states.
     *
     * @return TModel
     */
    public static function createOne(array $attributes = []): object
    {
        return static::new()->create($attributes, noProxy: true);
    }

    /**
     * @final
     *
     * A shortcut to create multiple models, based on a sequence, without states.
     *
     * @param iterable<array<string, mixed>>|callable(): iterable<array<string, mixed>> $sequence
     *
     * @return list<TModel>
     */
    public static function createSequence(iterable|callable $sequence): array
    {
        return static::new()->sequence($sequence)->create(noProxy: true);
    }

    /**
     * @final
     *
     * Try and find existing object for the given $attributes. If not found,
     * instantiate and persist.
     *
     * @return TModel
     */
    public static function findOrCreate(array $attributes): object
    {
        try {
            if ($found = static::repository()->find($attributes)) {
                return $found->_real();
            }
        } catch (FoundryBootException) {
        }

        return static::new()->create($attributes, noProxy: true);
    }

    /**
     * @final
     *
     * @see RepositoryDecorator::first()
     *
     * @return TModel
     *
     * @throws \RuntimeException If no entities exist
     */
    public static function first(string $sortedField = 'id'): object
    {
        if (null === $proxy = static::repository()->first($sortedField)) {
            throw new \RuntimeException(\sprintf('No "%s" objects persisted.', static::class()));
        }

        return $proxy->_real();
    }

    /**
     * @final
     *
     * @see RepositoryDecorator::last()
     *
     * @return TModel
     *
     * @throws \RuntimeException If no entities exist
     */
    public static function last(string $sortedField = 'id'): object
    {
        if (null === $proxy = static::repository()->last($sortedField)) {
            throw new \RuntimeException(\sprintf('No "%s" objects persisted.', static::class()));
        }

        return $proxy->_real();
    }

    /**
     * @final
     *
     * @see RepositoryDecorator::random()
     *
     * @return TModel
     */
    public static function random(array $attributes = []): object
    {
        return static::repository()->random($attributes)->_real();
    }

    /**
     * @final
     *
     * Fetch one random object and create a new object if none exists.
     *
     * @return TModel
     */
    public static function randomOrCreate(array $attributes = []): object
    {
        try {
            return static::repository()->random($attributes)->_real();
        } catch (\RuntimeException) {
            return static::new()->create($attributes, noProxy: true);
        }
    }

    /**
     * @final
     *
     * @see RepositoryDecorator::randomSet()
     *
     * @return list<TModel>
     */
    public static function randomSet(int $number, array $attributes = []): array
    {
        return self::unproxy(static::repository()->randomSet($number, $attributes));
    }

    /**
     * @final
     *
     * @see RepositoryDecorator::randomRange()
     *
     * @return list<TModel>
     */
    public static function randomRange(int $min, int $max, array $attributes = []): array
    {
        return self::unproxy(static::repository()->randomRange($min, $max, $attributes));
    }

    /**
     * @see RepositoryDecorator::count()
     */
    final public static function count(array $criteria = []): int
    {
        return static::repository()->count($criteria);
    }

    /**
     * @see RepositoryDecorator::truncate()
     */
    final public static function truncate(): void
    {
        static::repository()->truncate();
    }

    /**
     * @final
     *
     * @see RepositoryDecorator::findAll()
     *
     * @return list<TModel>
     */
    public static function all(): array
    {
        return self::unproxy(static::repository()->findAll());
    }

    /**
     * @final
     *
     * @see RepositoryDecorator::find()
     *
     * @phpstan-param TModel|array|mixed $criteria
     *
     * @return TModel
     *
     * @throws \RuntimeException If no entity found
     */
    public static function find($criteria): object
    {
        if (null === $proxy = static::repository()->find($criteria)) {
            throw new \RuntimeException(\sprintf('Could not find "%s" object.', static::class()));
        }

        return $proxy->_real();
    }

    /**
     * @final
     *
     * @see RepositoryDecorator::findBy()
     *
     * @return list<TModel>
     */
    public static function findBy(array $attributes): array
    {
        return self::unproxy(static::repository()->findBy($attributes));
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
     * @phpstan-return RepositoryDecorator<TModel>
     */
    final public static function repository(): RepositoryDecorator
    {
        return static::configuration()->repositoryFor(static::class());
    }

    /** @phpstan-return class-string<TModel> */
    abstract public static function class(): string;

    /**
     * Override to add default instantiator and default afterInstantiate/afterPersist events.
     *
     * @return static
     */
    #[\ReturnTypeWillChange]
    protected function initialize()
    {
        return $this;
    }

    /**
     * @deprecated use with() instead
     */
    final protected function addState(array|callable $attributes = []): static
    {
        trigger_deprecation('zenstruck\foundry', '1.37.0', 'Method "%s()" is deprecated and will be removed in version 2.0. Use "%s::with()" instead.', __METHOD__, Factory::class);

        return $this->with($attributes);
    }

    abstract protected function defaults(): array|callable;

    /**
     * @param  list<Proxy<TModel>> $proxies
     * @return list<TModel>
     */
    private static function unproxy(array $proxies): array
    {
        return \array_map(
            static fn(Proxy $proxy) => $proxy->_real(),
            $proxies
        );
    }
}
