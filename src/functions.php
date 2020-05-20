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
 * @see Factory::persist()
 *
 * @return Proxy|object
 */
function persist(string $class, $attributes = [], ?bool $proxy = null): object
{
    return factory($class)->persist($attributes, $proxy);
}

/**
 * @see Factory::persistMany()
 *
 * @return Proxy[]|object[]
 */
function persist_many(int $number, string $class, $attributes = [], ?bool $proxy = null): object
{
    return factory($class)->persistMany($number, $attributes, $proxy);
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
function instantiate_many(int $number, string $class, $attributes = []): object
{
    return factory($class)->instantiateMany($number, $attributes);
}

/**
 * @see PersistenceManager::repositoryFor()
 *
 * @return RepositoryProxy|ObjectRepository
 */
function repository(string $objectOrClass, bool $proxy = true): ObjectRepository
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
