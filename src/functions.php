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
 * @return AnonymousFactory<TObject>
 */
function factory(string $class, array|callable $defaultAttributes = []): AnonymousFactory
{
    return new AnonymousFactory($class, $defaultAttributes);
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
    return factory($class)->create($attributes);
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
    return factory($class)->many($number)->create($attributes);
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
    return factory($class)->withoutPersisting()->create($attributes);
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
    return factory($class)->withoutPersisting()->many($number)->create($attributes);
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
    return new LazyValue($factory);
}
