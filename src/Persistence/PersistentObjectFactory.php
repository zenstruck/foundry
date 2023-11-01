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
use Zenstruck\Foundry\ObjectFactory;

/**
 * @template TModel of object
 * @template-extends ObjectFactory<TModel>
 *
 * @method static TModel[] createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<TModel> createMany(int $number, array|callable $attributes = [])
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class PersistentObjectFactory extends ObjectFactory
{
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
                return $found;
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
        return static::repository()->first($sortedField) ?? throw new \RuntimeException(\sprintf('No "%s" objects persisted.', static::class()));
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
        return static::repository()->last($sortedField) ?? throw new \RuntimeException(\sprintf('No "%s" objects persisted.', static::class()));
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
        return static::repository()->random($attributes);
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
            return static::repository()->random($attributes);
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
        return static::repository()->randomSet($number, $attributes);
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
        return static::repository()->randomRange($min, $max, $attributes);
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
        return static::repository()->findAll();
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
        return static::repository()->find($criteria) ?? throw new \RuntimeException(\sprintf('Could not find "%s" object.', static::class()));
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
     * @phpstan-return RepositoryDecorator<TModel>
     *
     * @final
     */
    public static function repository(): RepositoryDecorator
    {
        return static::configuration()->repositoryFor(static::class(), proxy: false);
    }
}
