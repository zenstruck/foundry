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

use Symfony\Component\VarExporter\Internal\LazyObjectState;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Exception\PersistenceNotAvailable;
use Zenstruck\Foundry\Object\Hydrator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @property LazyObjectState $lazyObjectState
 * @method   object          initializeLazyObject()
 */
trait IsProxy
{
    private bool $_autoRefresh = true;

    public function _enableAutoRefresh(): static
    {
        $this->_autoRefresh = true;

        return $this;
    }

    public function _disableAutoRefresh(): static
    {
        $this->_autoRefresh = false;

        return $this;
    }

    public function _withoutAutoRefresh(callable $callback): static
    {
        $original = $this->_autoRefresh;
        $this->_autoRefresh = false;

        $callback($this);

        $this->_autoRefresh = $original;

        return $this;
    }

    public function _save(): static
    {
        Configuration::instance()->persistence()->save($this->initializeLazyObject());

        return $this;
    }

    public function _refresh(): static
    {
        $this->initializeLazyObject();
        $object = $this->lazyObjectState->realInstance;

        Configuration::instance()->persistence()->refresh($object);

        $this->lazyObjectState->realInstance = $object;

        return $this;
    }

    public function _delete(): static
    {
        Configuration::instance()->persistence()->delete($this->initializeLazyObject());

        return $this;
    }

    public function _get(string $property): mixed
    {
        $this->_autoRefresh();

        return Hydrator::get($this->initializeLazyObject(), $property);
    }

    public function _set(string $property, mixed $value): static
    {
        $this->_autoRefresh();

        Hydrator::set($this->initializeLazyObject(), $property, $value);

        return $this;
    }

    public function _real(): object
    {
        try {
            // we don't want the auto-refresh mechanism to break "real" object retrieval
            $this->_autoRefresh();
        } catch (\Throwable) {
        }

        return $this->initializeLazyObject();
    }

    public function _repository(): ProxyRepositoryDecorator
    {
        return new ProxyRepositoryDecorator(parent::class);
    }

    private function _autoRefresh(): void
    {
        if (!$this->_autoRefresh) {
            return;
        }

        try {
            // we don't want that "transparent" calls to _refresh() to trigger a PersistenceNotAvailable
            $this->_refresh();
        } catch (PersistenceNotAvailable) {
        }
    }
}
