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
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\RepositoryProxy;
use function Zenstruck\Foundry\anonymous;

/**
 * @see Configuration::repositoryFor()
 *
 * @template TObject of object
 *
 * @param class-string<TObject> $class
 *
 * @return RepositoryProxy<TObject>
 */
function repository(string $class): RepositoryProxy
{
    return Factory::configuration()->repositoryFor($class);
}

/**
 * @see Factory::create()
 *
 * @return TObject
 *
 * @template TObject of object
 * @phpstan-param class-string<TObject> $class
 */
function persist(string $class, array|callable $attributes = []): object
{
    return anonymous($class)->create($attributes)->_real();
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
