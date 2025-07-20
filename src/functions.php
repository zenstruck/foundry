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
use Zenstruck\Foundry\Object\Hydrator;

function faker(): Faker\Generator
{
    return Configuration::instance()->faker;
}

/**
 * Create an anonymous factory for the given class.
 *
 * @template T of object
 *
 * @param class-string<T>                                       $class
 * @param array<string,mixed>|callable(int):array<string,mixed> $attributes
 *
 * @return ObjectFactory<T>
 */
function factory(string $class, array|callable $attributes = []): ObjectFactory
{
    return AnonymousFactoryGenerator::create($class, ObjectFactory::class)::new($attributes);
}

/**
 * Instantiate the given class.
 *
 * @template T of object
 *
 * @param class-string<T>                                       $class
 * @param array<string,mixed>|callable(int):array<string,mixed> $attributes
 *
 * @return T
 */
function object(string $class, array|callable $attributes = []): object
{
    return factory($class, $attributes)->create();
}

/**
 * "Force set" (using reflection) an object property.
 *
 * @template T of object
 * @param T $object
 *
 * @return T
 */
function set(object $object, string $property, mixed $value): object
{
    Hydrator::set($object, $property, $value);

    return $object;
}

/**
 * "Force get" (using reflection) an object property.
 */
function get(object $object, string $property): mixed
{
    return Hydrator::get($object, $property);
}

/**
 * Create a "lazy" factory attribute which will only be evaluated
 * if used.
 *
 * @param callable():mixed $factory
 */
function lazy(callable $factory): LazyValue
{
    return LazyValue::new($factory);
}

/**
 * Same as {@see lazy()} but subsequent evaluations will return the
 * same value.
 *
 * @param callable():mixed $factory
 */
function memoize(callable $factory): LazyValue
{
    return LazyValue::memoize($factory);
}

/**
 * Allows to force a single property.
 */
function force(mixed $value): ForceValue
{
    return new ForceValue($value);
}
