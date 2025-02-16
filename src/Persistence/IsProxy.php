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

use Symfony\Component\VarExporter\LazyProxyTrait;
use Zenstruck\Assert;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Exception\PersistenceNotAvailable;
use Zenstruck\Foundry\Object\Hydrator;
use Zenstruck\Foundry\Persistence\Exception\RefreshObjectFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @mixin LazyProxyTrait
 */
trait IsProxy // @phpstan-ignore trait.unused
{
    private static array $_autoRefresh = [];

    public function _enableAutoRefresh(): static
    {
        $this->_setAutoRefresh(true);

        return $this;
    }

    public function _disableAutoRefresh(): static
    {
        $this->_setAutoRefresh(false);

        return $this;
    }

    public function _withoutAutoRefresh(callable $callback): static
    {
        $original = $this->_getAutoRefresh();
        $this->_setAutoRefresh(false);

        $callback($this);

        $this->_setAutoRefresh($original);

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

    public function _real(bool $withAutoRefresh = true): object
    {
        if ($withAutoRefresh) {
            try {
                // we don't want the auto-refresh mechanism to break "real" object retrieval
                $this->_autoRefresh();
            } catch (\Throwable) {
            }
        }

        return $this->initializeLazyObject();
    }

    public function _repository(): ProxyRepositoryDecorator
    {
        return new ProxyRepositoryDecorator(parent::class);
    }

    public function _assertPersisted(string $message = '{entity} is not persisted.'): static
    {
        Assert::that($this->isPersisted())->isTrue($message, ['entity' => parent::class]);

        return $this;
    }

    public function _assertNotPersisted(string $message = '{entity} is persisted but it should not be.'): static
    {
        Assert::that($this->isPersisted())->isFalse($message, ['entity' => parent::class]);

        return $this;
    }

    public function _initializeLazyObject(): void
    {
        $this->initializeLazyObject();
    }

    private function isPersisted(): bool
    {
        $this->initializeLazyObject();
        $object = $this->lazyObjectState->realInstance;

        return Configuration::instance()->persistence()->isPersisted($object);
    }

    private function _autoRefresh(): void
    {
        if (!$this->_getAutoRefresh()) {
            return;
        }

        try {
            // we don't want that "transparent" calls to _refresh() to trigger a PersistenceNotAvailable exception
            // or a RefreshObjectFailed exception when the object was deleted
            $this->_refresh();
        } catch (PersistenceNotAvailable|RefreshObjectFailed $e) {
            if ($e instanceof RefreshObjectFailed && false === $e->objectWasDeleted()) {
                throw $e;
            }
        }
    }

    private function _getAutoRefresh(): bool
    {
        $real = $this->initializeLazyObject();

        static::$_autoRefresh[\spl_object_id($real)] ??= true;

        return static::$_autoRefresh[\spl_object_id($real)];
    }

    private function _setAutoRefresh(bool $autoRefresh): void
    {
        $real = $this->initializeLazyObject();

        static::$_autoRefresh[\spl_object_id($real)] = $autoRefresh;
    }

    // used in ProxyGenerator
    private function unproxyArgs(array $args): array
    {
        return \array_map(unproxy(...), $args);
    }
}
