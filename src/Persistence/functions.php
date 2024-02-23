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
use Zenstruck\Foundry\AnonymousFactoryGenerator;
use Zenstruck\Foundry\Configuration;

/**
 * @template T of object
 *
 * @param class-string<T> $class
 *
 * @return RepositoryDecorator<T,ObjectRepository<T>>
 */
function repository(string $class): RepositoryDecorator
{
    return new RepositoryDecorator($class); // @phpstan-ignore-line
}

/**
 * @template T of object
 *
 * @param class-string<T> $class
 *
 * @return ProxyRepositoryDecorator<T,ObjectRepository<T>>
 */
function proxy_repository(string $class): ProxyRepositoryDecorator
{
    return new ProxyRepositoryDecorator($class); // @phpstan-ignore-line
}

/**
 * Create an anonymous "persistent" factory for the given class.
 *
 * @template T of object
 *
 * @param class-string<T>                                       $class
 * @param array<string,mixed>|callable(int):array<string,mixed> $attributes
 *
 * @return PersistentObjectFactory<T>
 */
function persistent_factory(string $class, array|callable $attributes = []): PersistentObjectFactory
{
    return AnonymousFactoryGenerator::create($class, PersistentObjectFactory::class)::new($attributes);
}

/**
 * Create an anonymous "persistent with proxy" factory for the given class.
 *
 * @template T of object
 *
 * @param class-string<T>                                       $class
 * @param array<string,mixed>|callable(int):array<string,mixed> $attributes
 *
 * @return PersistentProxyObjectFactory<T>
 */
function proxy_factory(string $class, array|callable $attributes = []): PersistentProxyObjectFactory
{
    return AnonymousFactoryGenerator::create($class, PersistentProxyObjectFactory::class)::new($attributes);
}

/**
 * Instantiate and "persist" the given class.
 *
 * @template T of object
 *
 * @param class-string<T>                                       $class
 * @param array<string,mixed>|callable(int):array<string,mixed> $attributes
 *
 * @return T
 */
function persist(string $class, array|callable $attributes = []): object
{
    return persistent_factory($class, $attributes)->andPersist()->create();
}

/**
 * Create an auto-refreshable proxy for the object.
 *
 * @template T of object
 *
 * @param T $object
 *
 * @return T&Proxy<T>
 */
function proxy(object $object): object
{
    return ProxyGenerator::wrap($object);
}

/**
 * Recursively unwrap all proxies.
 *
 * @template T
 *
 * @param T $what
 *
 * @return T
 */
function unproxy(mixed $what): mixed
{
    return ProxyGenerator::unwrap($what);
}

/**
 * @template T of object
 *
 * @param T $object
 *
 * @return T
 */
function save(object $object): object
{
    return Configuration::instance()->persistence()->save($object);
}

/**
 * @template T of object
 *
 * @param T $object
 *
 * @return T
 */
function refresh(object &$object): object
{
    return Configuration::instance()->persistence()->refresh($object);
}

/**
 * @template T of object
 *
 * @param T $object
 *
 * @return T
 */
function delete(object $object): object
{
    return Configuration::instance()->persistence()->delete($object);
}

/**
 * @param callable():void $callback
 */
function flush_after(callable $callback): void
{
    Configuration::instance()->persistence()->flushAfter($callback);
}

/**
 * Disable persisting factories globally.
 */
function disable_persisting(): void
{
    Configuration::instance()->persistence()->disablePersisting();
}

/**
 * Enable persisting factories globally.
 */
function enable_persisting(): void
{
    Configuration::instance()->persistence()->enablePersisting();
}
