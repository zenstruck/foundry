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
function create(string $class, $attributes = []): object
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
 * @see Factory::instantiate()
 */
function instantiate(string $class, $attributes = []): object
{
    return factory($class)->instantiate($attributes);
}

/**
 * @see Factory::instantiateMany()
 */
function instantiate_many(int $number, string $class, $attributes = []): array
{
    return factory($class)->instantiateMany($number, $attributes);
}

/**
 * @see PersistenceManager::repositoryFor()
 */
function repository($objectOrClass): RepositoryProxy
{
    return PersistenceManager::repositoryFor($objectOrClass);
}

/**
 * @see Factory::faker()
 */
function faker(): Faker\Generator
{
    return Factory::faker();
}
