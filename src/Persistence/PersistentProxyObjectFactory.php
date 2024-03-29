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
use Zenstruck\Foundry\FactoryCollection; // keep me!

/**
 * @template TModel of object
 * @template-extends PersistentObjectFactory<TModel>
 *
 * @method static Proxy[]|TModel[] createMany(int $number, array|callable $attributes = [])
 *
 * @phpstan-method FactoryCollection<Proxy<TModel>> sequence(iterable<array<string, mixed>>|callable(): iterable<array<string, mixed>> $sequence)
 * @phpstan-method FactoryCollection<Proxy<TModel>> many(int $min, int|null $max = null)
 *
 * @phpstan-method static list<Proxy<TModel>> createSequence(iterable<array<string, mixed>>|callable(): iterable<array<string, mixed>> $sequence)
 * @phpstan-method static list<Proxy<TModel>> createMany(int $number, array|callable $attributes = [])
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class PersistentProxyObjectFactory extends PersistentObjectFactory
{
    /**
     * @phpstan-return list<Proxy<TModel>>
     */
    public static function __callStatic(string $name, array $arguments): array
    {
        if ('createMany' !== $name) {
            throw new \BadMethodCallException(\sprintf('Call to undefined static method "%s::%s".', static::class, $name));
        }

        return static::new()->many($arguments[0])->create($arguments[1] ?? [], noProxy: false);
    }

    final public static function new(array|callable|string $defaultAttributes = [], string ...$states): static
    {
        if ((new \ReflectionClass(static::class()))->isFinal()) {
            trigger_deprecation(
                'zenstruck\foundry', '1.38.0',
                'Using a proxy factory with a final class is deprecated and will throw an error in Foundry 2.0. Use "Zenstruck\Foundry\ObjectFactory" instead (don\'t forget to remove all ->object() calls!).',
                self::class,
                static::class(),
            );
        }

        return parent::new($defaultAttributes, ...$states);
    }

    /**
     * @return Proxy<TModel>
     */
    final public function create(
        array|callable $attributes = [],
        /**
         * @deprecated
         * @internal
         */
        bool $noProxy = false,
    ): object {
        if (2 === \count(\func_get_args()) && !\str_starts_with(\debug_backtrace(options: \DEBUG_BACKTRACE_IGNORE_ARGS, limit: 1)[0]['class'] ?? '', 'Zenstruck\Foundry')) {
            trigger_deprecation('zenstruck\foundry', '1.38.0', 'Parameter "$noProxy" of method "%s()" is deprecated and will be removed in Foundry 2.0.', __METHOD__);
        }

        return Factory::create($attributes, noProxy: false);
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
     * @see ProxyRepositoryDecorator::first()
     *
     * @return Proxy<TModel>&TModel
     * @phpstan-return Proxy<TModel>
     *
     * @throws \RuntimeException If no entities exist
     */
    final public static function first(string $sortedField = 'id'): Proxy
    {
        if (null === $proxy = static::repository()->first($sortedField)) {
            throw new \RuntimeException(\sprintf('No "%s" objects persisted.', static::class()));
        }

        return $proxy;
    }

    /**
     * @see ProxyRepositoryDecorator::last()
     *
     * @return Proxy<TModel>&TModel
     * @phpstan-return Proxy<TModel>
     *
     * @throws \RuntimeException If no entities exist
     */
    final public static function last(string $sortedField = 'id'): Proxy
    {
        if (null === $proxy = static::repository()->last($sortedField)) {
            throw new \RuntimeException(\sprintf('No "%s" objects persisted.', static::class()));
        }

        return $proxy;
    }

    /**
     * @see ProxyRepositoryDecorator::random()
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
     * @see ProxyRepositoryDecorator::randomSet()
     *
     * @return list<TModel&Proxy<TModel>>
     * @phpstan-return list<Proxy<TModel>>
     */
    final public static function randomSet(int $number, array $attributes = []): array
    {
        return static::repository()->randomSet($number, $attributes);
    }

    /**
     * @see ProxyRepositoryDecorator::randomRange()
     *
     * @return list<TModel&Proxy<TModel>>
     * @phpstan-return list<Proxy<TModel>>
     */
    final public static function randomRange(int $min, int $max, array $attributes = []): array
    {
        return static::repository()->randomRange($min, $max, $attributes);
    }

    /**
     * @see ProxyRepositoryDecorator::findAll()
     *
     * @return list<TModel&Proxy<TModel>>
     * @phpstan-return list<Proxy<TModel>>
     */
    final public static function all(): array
    {
        return static::repository()->findAll();
    }

    /**
     * @see ProxyRepositoryDecorator::find()
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
            throw new \RuntimeException(\sprintf('Could not find "%s" object.', static::class()));
        }

        return $proxy;
    }

    /**
     * @see ProxyRepositoryDecorator::findBy()
     *
     * @return list<TModel&Proxy<TModel>>
     * @phpstan-return list<Proxy<TModel>>
     */
    final public static function findBy(array $attributes): array
    {
        return static::repository()->findBy($attributes);
    }

    /**
     * @phpstan-return ProxyRepositoryDecorator<TModel>
     */
    final public static function repository(): ProxyRepositoryDecorator
    {
        return static::configuration()->repositoryFor(static::class(), proxy: true);
    }
}
