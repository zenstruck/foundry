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
    public function _enableAutoRefresh(): static;

    public function _disableAutoRefresh(): static;

    /**
     * @param callable(static):void $callback
     */
    public function _withoutAutoRefresh(callable $callback): static;

    public function _save(): static;

    public function _refresh(): static;

    public function _delete(): static;

    public function _get(string $property): mixed;

    public function _set(string $property, mixed $value): static;

    /**
     * @return T
     */
    public function _real(): object;

    /**
     * @return ProxyRepositoryDecorator<T,ObjectRepository<T>>
     */
    public function _repository(): ProxyRepositoryDecorator;
}
