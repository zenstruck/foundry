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
use Zenstruck\Assert;
use Zenstruck\Foundry\AnonymousFactoryGenerator;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\Exception\RefreshObjectFailed;

/**
 * @template T of object
 *
 * @param class-string<T> $class
 *
 * @return RepositoryDecorator<T,ObjectRepository<T>>
 */
function repository(string $class): RepositoryDecorator
{
    return new RepositoryDecorator($class, Configuration::instance()->isInMemoryEnabled()); // @phpstan-ignore return.type
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
 *
 * @throws RefreshObjectFailed
 */
function refresh(object &$object): object
{
    return Configuration::instance()->persistence()->refresh($object);
}

/**
 * For refreshing all persistent objects created by Foundry, that are currently in use.
 */
function refresh_all(): void
{
    $objectsTracker = Configuration::instance()->persistedObjectsTracker;

    if (null === $objectsTracker) {
        throw new \BadMethodCallException('Cannot use refresh_all() if auto refresh with lazy objects is not enabled.');
    }

    $objectsTracker->refresh();
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
 * @template T
 *
 * @param callable():T $callback
 *
 * @return T
 */
function flush_after(callable $callback): mixed
{
    return Configuration::instance()->persistence()->flushAfter($callback);
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

function assert_persisted(object $object, string $message = '{entity} is not persisted.'): object
{
    Configuration::instance()->assertPersistenceEnabled();

    Assert::that(
        Configuration::instance()->persistence()->isPersisted($object)
    )->isTrue($message, ['entity' => $object::class]);

    return $object;
}

function assert_not_persisted(object $object, string $message = '{entity} is persisted.'): object
{
    Configuration::instance()->assertPersistenceEnabled();

    Assert::that(
        Configuration::instance()->persistence()->isPersisted($object)
    )->isFalse($message, ['entity' => $object::class]);

    return $object;
}
