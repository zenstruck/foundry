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
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @template T of object
 *
 * @param class-string<T> $class
 *
 * @deprecated
 *
 * @return AnonymousFactory<T>
 */
function factory(string $class, array|\Closure $defaultAttributes = []): AnonymousFactory
{
    trigger_deprecation('zenstruck\foundry', '1.30', 'Usage of "factory()" function is deprecated and will be removed in 2.0. Use the "anonymous()" or "repository()" functions instead.');

    return new AnonymousFactory($class, $defaultAttributes);
}

/**
 * @see BaseFactory::new()
 *
 * @template T of object
 *
 * @param class-string<T> $class
 *
 * @return PersistentObjectFactory<T>
 */
function anonymous(string $class, array|callable $defaultAttributes = []): PersistentObjectFactory
{
    $anonymousClassName = 'AnonymousFactory'.\str_replace('\\', '', $class);
    $anonymousClassName = \preg_replace('/[^a-zA-Z0-9_]/', '', $anonymousClassName); // sanitize for anonymous classes

    if (!\class_exists($anonymousClassName)) { // @phpstan-ignore-line
        $factoryClass = PersistentObjectFactory::class;

        $anonymousClassCode = <<<CODE
            /**
             * @internal
             */
             final class {$anonymousClassName} extends {$factoryClass}
             {
                 public static function class(): string
                 {
                     return "{$class}";
                 }

                 protected function getDefaults(): array
                 {
                     return [];
                 }
             }
            CODE;

        // todo: dump class in var/cache?
        eval($anonymousClassCode); // @phpstan-ignore-line
    }

    return $anonymousClassName::new($defaultAttributes); // @phpstan-ignore-line
}

/**
 * @see BaseFactory::create()
 *
 * @return Proxy&T
 *
 * @template T of object
 * @phpstan-param class-string<T> $class
 * @phpstan-return Proxy<T>
 */
function create(string $class, array|callable $attributes = []): Proxy
{
    return anonymous($class)->create($attributes);
}

/**
 * @see BaseFactory::createMany()
 *
 * @return Proxy[]|object[]
 *
 * @template T of object
 * @phpstan-param class-string<T> $class
 * @phpstan-return list<Proxy<T>>
 */
function create_many(int $number, string $class, array|callable $attributes = []): array
{
    return anonymous($class)->many($number)->create($attributes);
}

/**
 * Instantiate object without persisting.
 *
 * @return Proxy&T "unpersisted" Proxy wrapping the instantiated object
 *
 * @template T of object
 * @phpstan-param class-string<T> $class
 * @phpstan-return Proxy<T>
 */
function instantiate(string $class, array|callable $attributes = []): Proxy
{
    return anonymous($class)->withoutPersisting()->create($attributes);
}

/**
 * Instantiate X objects without persisting.
 *
 * @return Proxy[]&object[] "unpersisted" Proxy's wrapping the instantiated objects
 *
 * @template T of object
 *
 * @phpstan-param class-string<T> $class
 * @phpstan-return list<Proxy<T>>
 */
function instantiate_many(int $number, string $class, array|callable $attributes = []): array
{
    return anonymous($class)->withoutPersisting()->many($number)->create($attributes);
}

/**
 * @see PersistentObjectFactory::repositoryFor()
 *
 * @template T of object
 *
 * @param T|class-string<T> $objectOrClass
 *
 * @return RepositoryProxy<T>
 */
function repository(object|string $objectOrClass): RepositoryProxy
{
    if (!\class_exists(PersistentObjectFactory::class)) {
        throw new \LogicException('The "repository()" function can only be used when the "zenstruck/foundry-persistence" package is installed.');
    }

    return PersistentObjectFactory::persistenceManager()->repositoryFor($objectOrClass);
}

/**
 * @see BaseFactory::faker()
 */
function faker(): Faker\Generator
{
    return BaseFactory::faker();
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
