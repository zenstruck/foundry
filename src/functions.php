<?php

namespace Zenstruck\Foundry;

use Faker;

/**
 * @see Factory::__construct()
 */
function factory(string $class, $defaultAttributes = []): Factory
{
    return new Factory($class, $defaultAttributes);
}

/**
 * @see Factory::create()
 *
 * @return Proxy|object
 */
function create(string $class, $attributes = []): Proxy
{
    return factory($class)->create($attributes);
}

/**
 * @see Factory::createMany()
 *
 * @return Proxy[]|object[]
 */
function create_many(int $number, string $class, $attributes = []): array
{
    return factory($class)->createMany($number, $attributes);
}

/**
 * Instantiate object without persisting.
 *
 * @return Proxy|object "unpersisted" Proxy wrapping the instantiated object
 */
function instantiate(string $class, $attributes = []): Proxy
{
    return factory($class)->withoutPersisting()->create($attributes);
}

/**
 * Instantiate X objects without persisting.
 *
 * @return Proxy[]|object[] "unpersisted" Proxy's wrapping the instantiated objects
 */
function instantiate_many(int $number, string $class, $attributes = []): array
{
    return factory($class)->withoutPersisting()->createMany($number, $attributes);
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
