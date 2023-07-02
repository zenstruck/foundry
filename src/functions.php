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

use Faker;

/**
 * @see Factory::__construct()
 *
 * @template TObject of object
 *
 * @param class-string<TObject> $class
 *
 * @deprecated
 *
 * @return AnonymousFactory<TObject>
 */
function factory(string $class, array|callable $defaultAttributes = []): AnonymousFactory
{
    trigger_deprecation('zenstruck\foundry', '1.30', 'Usage of "factory()" function is deprecated and will be removed in 2.0. Use the "anonymous()" or "repository()" functions instead.');

    return new AnonymousFactory($class, $defaultAttributes);
}

/**
 * @see Factory::__construct()
 *
 * @template TObject of object
 *
 * @param class-string<TObject> $class
 *
 * @return Factory<TObject>
 */
function anonymous(string $class, array|callable $defaultAttributes = []): Factory
{
    return new class($class, $defaultAttributes) extends Factory {};
}

/**
 * @see Factory::create()
 *
 * @return Proxy&TObject
 *
 * @template TObject of object
 * @phpstan-param class-string<TObject> $class
 * @phpstan-return Proxy<TObject>
 */
function create(string $class, array|callable $attributes = []): Proxy
{
    return anonymous($class)->create($attributes);
}

/**
 * @see Factory::createMany()
 *
 * @return Proxy[]|object[]
 *
 * @template TObject of object
 * @phpstan-param class-string<TObject> $class
 * @phpstan-return list<Proxy<TObject>>
 */
function create_many(int $number, string $class, array|callable $attributes = []): array
{
    return anonymous($class)->many($number)->create($attributes);
}

/**
 * Instantiate object without persisting.
 *
 * @return Proxy&TObject "unpersisted" Proxy wrapping the instantiated object
 *
 * @template TObject of object
 * @phpstan-param class-string<TObject> $class
 * @phpstan-return Proxy<TObject>
 */
function instantiate(string $class, array|callable $attributes = []): Proxy
{
    return anonymous($class)->withoutPersisting()->create($attributes);
}

/**
 * Instantiate X objects without persisting.
 *
 * @return Proxy[]|object[] "unpersisted" Proxy's wrapping the instantiated objects
 *
 * @template TObject of object
 * @phpstan-param class-string<TObject> $class
 * @phpstan-return list<Proxy<TObject>>
 */
function instantiate_many(int $number, string $class, array|callable $attributes = []): array
{
    return anonymous($class)->withoutPersisting()->many($number)->create($attributes);
}

/**
 * @see Configuration::repositoryFor()
 *
 * @template TObject of object
 *
 * @param TObject|class-string<TObject> $objectOrClass
 *
 * @return RepositoryProxy<TObject>
 */
function repository(object|string $objectOrClass): RepositoryProxy
{
    return Factory::configuration()->repositoryFor($objectOrClass);
}

/**
 * @see Factory::faker()
 */
function faker(): Faker\Generator
{
    return Factory::faker();
}

/**
 * @see LazyValue
 *
 * @param callable():mixed $factory
 */
function lazy(callable $factory): LazyValue
{
    return LazyValue::new($factory);
}

/**
 * @see LazyValue::memoize
 *
 * @param callable():mixed $factory
 */
function memoize(callable $factory): LazyValue
{
    return LazyValue::memoize($factory);
}
