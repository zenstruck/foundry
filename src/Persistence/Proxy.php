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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of object
 * @mixin T
 */
interface Proxy
{
    /**
     * @psalm-return T&Proxy<T>
     * @phpstan-return static
     */
    public function _enableAutoRefresh(): static;

    /**
     * @psalm-return T&Proxy<T>
     * @phpstan-return static
     */
    public function _disableAutoRefresh(): static;

    /**
     * @param callable(static):void $callback
     * @psalm-return T&Proxy<T>
     * @phpstan-return static
     */
    public function _withoutAutoRefresh(callable $callback): static;

    /**
     * @psalm-return T&Proxy<T>
     * @phpstan-return static
     */
    public function _save(): static;

    /**
     * @psalm-return T&Proxy<T>
     * @phpstan-return static
     */
    public function _refresh(): static;

    /**
     * @psalm-return T&Proxy<T>
     * @phpstan-return static
     */
    public function _delete(): static;

    public function _get(string $property): mixed;

    /**
     * @psalm-return T&Proxy<T>
     * @phpstan-return static
     */
    public function _set(string $property, mixed $value): static;

    /**
     * @return T
     */
    public function _real(bool $withAutoRefresh = true): object;

    /**
     * @psalm-return T&Proxy<T>
     * @phpstan-return static
     */
    public function _assertPersisted(string $message = '{entity} is not persisted.'): static;

    /**
     * @psalm-return T&Proxy<T>
     * @phpstan-return static
     */
    public function _assertNotPersisted(string $message = '{entity} is persisted but it should not be.'): static;

    /**
     * @return ProxyRepositoryDecorator<T,ObjectRepository<T>>
     */
    public function _repository(): ProxyRepositoryDecorator;

    /**
     * @internal
     */
    public function _initializeLazyObject(): void;
}
