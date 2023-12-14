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
     * @return RepositoryDecorator<TProxiedObject>
     */
    public function _repository(): RepositoryDecorator;

    public function _enableAutoRefresh(): static;

    public function _disableAutoRefresh(): static;

    /**
     * Ensures "autoRefresh" is disabled when executing $callback. Re-enables
     * "autoRefresh" after executing callback if it was enabled.
     *
     * @param callable $callback (object|Proxy $object): void
     */
    public function _withoutAutoRefresh(callable $callback): static;
}
