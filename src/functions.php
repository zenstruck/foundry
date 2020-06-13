<?php

namespace Zenstruck\Foundry;

use Doctrine\Persistence\ObjectRepository;
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
function create(string $class, $attributes = [], ?bool $proxy = null): object
{
    return factory($class)->create($attributes, $proxy);
}

/**
 * @see Factory::createMany()
 *
 * @return Proxy[]|object[]
 */
function create_many(int $number, string $class, $attributes = [], ?bool $proxy = null): array
{
    return factory($class)->createMany($number, $attributes, $proxy);
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
 *
 * @return RepositoryProxy|ObjectRepository
 */
function repository($objectOrClass, bool $proxy = true): ObjectRepository
{
    return PersistenceManager::repositoryFor($objectOrClass, $proxy);
}

/**
 * @see Factory::faker()
 */
function faker(): Faker\Generator
{
    return Factory::faker();
}
