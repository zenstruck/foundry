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

use Zenstruck\Foundry\AnonymousFactoryGenerator;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;

/**
 * @param class-string<TObject> $class
 *
 * @return RepositoryDecorator<TObject>
 * @see Configuration::repositoryFor()
 *
 * @template TObject of object
 *
 */
function repository(string $class): RepositoryDecorator
{
    return Factory::configuration()->repositoryFor($class);
}

/**
 * @return TObject
 *
 * @template TObject of object
 * @phpstan-param class-string<TObject> $class
 * @see Factory::create()
 */
function persist(string $class, array|callable $attributes = []): object
{
    return persistent_factory($class)->create($attributes);
}

/**
 * @return Proxy<TObject>
 *
 * @template TObject of object
 * @phpstan-param class-string<TObject> $class
 * @see Factory::create()
 */
function persist_proxy(string $class, array|callable $attributes = []): Proxy
{
    return proxy_factory($class)->create($attributes);
}

/**
 * Create an anonymous "persistent" factory for the given class.
 *
 * @template T of object
 *
 * @param class-string<T> $class
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
 * @param class-string<T> $class
 * @param array<string,mixed>|callable(int):array<string,mixed> $attributes
 *
 * @return PersistentProxyObjectFactory<T>
 */
function proxy_factory(string $class, array|callable $attributes = []): PersistentProxyObjectFactory
{
    if ((new \ReflectionClass($class))->isFinal()) {
        throw new \RuntimeException(
            \sprintf('Cannot create PersistentProxyObjectFactory for final class "%s". Pass parameter "$withProxy" to false instead, or unfinalize "%1$s" class.', $class)
        );
    }

    return AnonymousFactoryGenerator::create($class, PersistentProxyObjectFactory::class)::new($attributes);
}


/**
 * @param callable():void $callback
 */
function flush_after(callable $callback): void
{
    Factory::configuration()->delayFlush($callback);
}

/**
 * Disable persisting factories globally.
 */
function disable_persisting(): void
{
    Factory::configuration()->disablePersist();
}

/**
 * Enable persisting factories globally.
 */
function enable_persisting(): void
{
    Factory::configuration()->enablePersist();
}
