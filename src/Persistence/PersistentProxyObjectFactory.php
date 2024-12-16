<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Persistence;

use Doctrine\Persistence\ObjectRepository;
use Zenstruck\Foundry\Configuration;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of object
 * @extends PersistentObjectFactory<T&Proxy<T>>
 */
abstract class PersistentProxyObjectFactory extends PersistentObjectFactory
{
    /**
     * @return class-string<T>
     */
    abstract public static function class(): string;

    /**
     * @return T|Proxy<T>
     * @phpstan-return T&Proxy<T>
     */
    final public function create(callable|array $attributes = []): object
    {
        $configuration = Configuration::instance();
        if ($configuration->inADataProvider()) {
            return ProxyGenerator::wrapFactory($this, $attributes);
        }

        return proxy(parent::create($attributes)); // @phpstan-ignore function.unresolvableReturnType
    }

    /**
     * @return T|Proxy<T>
     * @phpstan-return T&Proxy<T>
     */
    final public static function createOne(array|callable $attributes = []): mixed
    {
        return proxy(parent::createOne($attributes)); // @phpstan-ignore function.unresolvableReturnType
    }

    /**
     * @return T|Proxy<T>
     * @phpstan-return T&Proxy<T>
     */
    final public static function find(mixed $criteriaOrId): object
    {
        return proxy(parent::find($criteriaOrId)); // @phpstan-ignore function.unresolvableReturnType
    }

    /**
     * @return T|Proxy<T>
     * @phpstan-return T&Proxy<T>
     */
    final public static function findOrCreate(array $criteria): object
    {
        return proxy(parent::findOrCreate($criteria)); // @phpstan-ignore function.unresolvableReturnType
    }

    /**
     * @return T|Proxy<T>
     * @phpstan-return T&Proxy<T>
     */
    final public static function randomOrCreate(array $criteria = []): object
    {
        return proxy(parent::randomOrCreate($criteria)); // @phpstan-ignore function.unresolvableReturnType
    }

    /**
     * @return list<T&Proxy<T>>
     */
    final public static function randomSet(int $count, array $criteria = []): array
    {
        return \array_map(proxy(...), parent::randomSet($count, $criteria));
    }

    /**
     * @return list<T&Proxy<T>>
     */
    final public static function randomRange(int $min, int $max, array $criteria = []): array
    {
        return \array_map(proxy(...), parent::randomRange($min, $max, $criteria));
    }

    /**
     * @return list<T&Proxy<T>>
     */
    final public static function findBy(array $criteria): array
    {
        return \array_map(proxy(...), parent::findBy($criteria));
    }

    /**
     * @return T|Proxy<T>
     * @phpstan-return T&Proxy<T>
     */
    final public static function random(array $criteria = []): object
    {
        return proxy(parent::random($criteria)); // @phpstan-ignore function.unresolvableReturnType
    }

    /**
     * @return T|Proxy<T>
     * @phpstan-return T&Proxy<T>
     */
    final public static function first(string $sortBy = 'id'): object
    {
        return proxy(parent::first($sortBy)); // @phpstan-ignore function.unresolvableReturnType
    }

    /**
     * @return T|Proxy<T>
     * @phpstan-return T&Proxy<T>
     */
    final public static function last(string $sortBy = 'id'): object
    {
        return proxy(parent::last($sortBy)); // @phpstan-ignore function.unresolvableReturnType
    }

    /**
     * @return list<T&Proxy<T>>
     */
    final public static function all(): array
    {
        return \array_map(proxy(...), parent::all());
    }

    /**
     * @return ProxyRepositoryDecorator<T,ObjectRepository<T>>
     */
    final public static function repository(): ObjectRepository
    {
        Configuration::instance()->assertPersistenceEnabled();

        return new ProxyRepositoryDecorator(static::class()); // @phpstan-ignore argument.type, return.type
    }
}
