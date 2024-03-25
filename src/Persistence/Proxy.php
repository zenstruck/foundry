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

/**
 * @template TProxiedObject of object
 *
 * @mixin TProxiedObject
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @final
 */
interface Proxy
{
    /**
     * @return TProxiedObject
     */
    public function _real(): object;

    public function _save(): static;

    public function _delete(): static;

    public function _refresh(): static;

    public function _set(string $property, mixed $value): static;

    public function _get(string $property): mixed;

    /**
     * @return ProxyRepositoryDecorator<TProxiedObject>
     */
    public function _repository(): ProxyRepositoryDecorator;

    public function _enableAutoRefresh(): static;

    public function _disableAutoRefresh(): static;

    /**
     * Ensures "autoRefresh" is disabled when executing $callback. Re-enables
     * "autoRefresh" after executing callback if it was enabled.
     *
     * @param callable $callback (object|Proxy $object): void
     */
    public function _withoutAutoRefresh(callable $callback): static;

    /**
     * @return TProxiedObject
     *
     * @deprecated Use method "_real()" instead
     */
    public function object(): object;

    /**
     * @deprecated Use method "_save()" instead
     */
    public function save(): static;

    /**
     * @deprecated Use method "_delete()" instead
     */
    public function remove(): static;

    /**
     * @deprecated Use method "_refresh()" instead
     */
    public function refresh(): static;

    /**
     * @deprecated Use method "_set()" instead
     */
    public function forceSet(string $property, mixed $value): static;

    /**
     * @deprecated without replacement
     */
    public function forceSetAll(array $properties): static;

    /**
     * @deprecated Use method "_get()" instead
     */
    public function forceGet(string $property): mixed;

    /**
     * @deprecated Use method "_repository()" instead
     */
    public function repository(): RepositoryDecorator;

    /**
     * @deprecated Use method "_enableAutoRefresh()" instead
     */
    public function enableAutoRefresh(): static;

    /**
     * @deprecated Use method "_disableAutoRefresh()" instead
     */
    public function disableAutoRefresh(): static;

    /**
     * @param callable $callback (object|Proxy $object): void
     *
     * @deprecated Use method "_withoutAutoRefresh()" instead
     */
    public function withoutAutoRefresh(callable $callback): static;

    /**
     * @deprecated without replacement
     */
    public function assertPersisted(string $message = '{entity} is not persisted.'): self;

    /**
     * @deprecated without replacement
     */
    public function assertNotPersisted(string $message = '{entity} is persisted but it should not be.'): self;
}
