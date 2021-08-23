<?php

namespace Zenstruck\Foundry;

use Faker;

/**
 * @see Factory::__construct()
 *
 * @template TObject of object
 * @psalm-param class-string<TObject> $class
 * @psalm-return AnonymousFactory<TObject>
 */
function factory(string $class, $defaultAttributes = []): AnonymousFactory
{
    return new AnonymousFactory($class, $defaultAttributes);
}

/**
 * @see Factory::create()
 *
 * @return Proxy&TObject
 *
 * @template TObject of object
 * @psalm-param class-string<TObject> $class
 * @psalm-return Proxy<TObject>
 */
function create(string $class, $attributes = []): Proxy
{
    return factory($class)->create($attributes);
}

/**
 * @see Factory::createMany()
 *
 * @return Proxy[]|object[]
 *
 * @template TObject of object
 * @psalm-param class-string<TObject> $class
 * @psalm-return list<Proxy<TObject>>
 */
function create_many(int $number, string $class, $attributes = []): array
{
    return factory($class)->many($number)->create($attributes);
}

/**
 * Instantiate object without persisting.
 *
 * @return Proxy&TObject "unpersisted" Proxy wrapping the instantiated object
 *
 * @template TObject of object
 * @psalm-param class-string<TObject> $class
 * @psalm-return Proxy<TObject>
 */
function instantiate(string $class, $attributes = []): Proxy
{
    return factory($class)->withoutPersisting()->create($attributes);
}

/**
 * Instantiate X objects without persisting.
 *
 * @return Proxy[]|object[] "unpersisted" Proxy's wrapping the instantiated objects
 *
 * @template TObject of object
 * @psalm-param class-string<TObject> $class
 * @psalm-return list<Proxy<TObject>>
 */
function instantiate_many(int $number, string $class, $attributes = []): array
{
    return factory($class)->withoutPersisting()->many($number)->create($attributes);
}

/**
 * @see Configuration::repositoryFor()
 */
function repository($objectOrClass): RepositoryProxy
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
