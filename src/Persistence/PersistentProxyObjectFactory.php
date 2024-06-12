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
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\FactoryCollection; // keep me!

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of object
 * @extends PersistentObjectFactory<T&Proxy<T>>
 *
 * @phpstan-type InstantiatorCallable = Instantiator|callable(Parameters,class-string<T>):T
 * @phpstan-import-type Parameters from Factory
 *
 * @phpstan-method $this instantiateWith(InstantiatorCallable $instantiator)
 *
 * @phpstan-method FactoryCollection<T&Proxy<T>> sequence(iterable<array<string, mixed>>|callable(): iterable<array<string, mixed>> $sequence)
 * @phpstan-method FactoryCollection<T&Proxy<T>> many(int $min, int|null $max = null)
 *
 * @phpstan-method static list<T&Proxy<T>> createSequence(iterable<array<string, mixed>>|callable(): iterable<array<string, mixed>> $sequence)
 * @phpstan-method static list<T&Proxy<T>> createMany(int $number, array|callable $attributes = [])
 */
abstract class PersistentProxyObjectFactory extends PersistentObjectFactory
{
    /**
     * @return class-string<T>
     */
    abstract public static function class(): string;

    /**
     * @return T&Proxy<T>
     */
    final public static function find(mixed $criteriaOrId): object
    {
        return proxy(parent::find($criteriaOrId)); // @phpstan-ignore-line
    }

    /**
     * @return T&Proxy<T>
     */
    final public static function findOrCreate(array $criteria): object
    {
        return proxy(parent::findOrCreate($criteria)); // @phpstan-ignore-line
    }

    /**
     * @return T&Proxy<T>
     */
    final public static function randomOrCreate(array $criteria = []): object
    {
        return proxy(parent::randomOrCreate($criteria)); // @phpstan-ignore-line
    }

    /**
     * @return list<T&Proxy<T>>
     */
    final public static function randomSet(int $count, array $criteria = []): array
    {
        return \array_map(proxy(...), parent::randomSet($count, $criteria)); // @phpstan-ignore-line
    }

    /**
     * @return list<T&Proxy<T>>
     */
    final public static function randomRange(int $min, int $max, array $criteria = []): array
    {
        return \array_map(proxy(...), parent::randomRange($min, $max, $criteria)); // @phpstan-ignore-line
    }

    /**
     * @return list<T&Proxy<T>>
     */
    final public static function findBy(array $criteria): array
    {
        return \array_map(proxy(...), parent::findBy($criteria)); // @phpstan-ignore-line
    }

    /**
     * @return T&Proxy<T>
     */
    final public static function random(array $criteria = []): object
    {
        return proxy(parent::random($criteria)); // @phpstan-ignore-line
    }

    /**
     * @return T&Proxy<T>
     */
    final public static function first(string $sortBy = 'id'): object
    {
        return proxy(parent::first($sortBy)); // @phpstan-ignore-line
    }

    /**
     * @return T&Proxy<T>
     */
    final public static function last(string $sortBy = 'id'): object
    {
        return proxy(parent::last($sortBy)); // @phpstan-ignore-line
    }

    /**
     * @return list<T&Proxy<T>>
     */
    final public static function all(): array
    {
        return \array_map(proxy(...), parent::all()); // @phpstan-ignore-line
    }

    /**
     * @return ProxyRepositoryDecorator<T,ObjectRepository<T>>
     */
    final public static function repository(): ObjectRepository
    {
        Configuration::instance()->assertPersistanceEnabled();

        return new ProxyRepositoryDecorator(static::class()); // @phpstan-ignore-line
    }
}
