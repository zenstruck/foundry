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
use Symfony\Component\VarExporter\Exception\LogicException as VarExportLogicException;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Exception\PersistenceDisabled;
use Zenstruck\Foundry\Exception\PersistenceNotAvailable;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\Exception\NotEnoughObjects;
use Zenstruck\Foundry\Persistence\Exception\RefreshObjectFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of object
 * @extends ObjectFactory<T>
 *
 * @phpstan-import-type Parameters from Factory
 */
abstract class PersistentObjectFactory extends ObjectFactory
{
    private bool $persist;

    /** @var list<callable(T, Parameters):void> */
    private array $afterPersist = [];

    /** @var list<callable(T):void> */
    private array $tempAfterPersist = [];

    /**
     * @final
     *
     * @param mixed|Parameters $criteriaOrId
     *
     * @return T
     *
     * @throws \RuntimeException If no object found
     */
    public static function find(mixed $criteriaOrId): object
    {
        return static::repository()->findOrFail($criteriaOrId);
    }

    /**
     * @final
     *
     * @param Parameters $criteria
     *
     * @return T
     */
    public static function findOrCreate(array $criteria): object
    {
        try {
            $object = static::repository()->findOneBy($criteria);
        } catch (PersistenceNotAvailable|PersistenceDisabled) {
            $object = null;
        }

        return $object ?? static::createOne($criteria);
    }

    /**
     * @final
     *
     * @param Parameters $criteria
     *
     * @return T
     */
    public static function randomOrCreate(array $criteria = []): object
    {
        try {
            return static::repository()->random($criteria);
        } catch (NotEnoughObjects|PersistenceNotAvailable|PersistenceDisabled) {
            return static::createOne($criteria);
        }
    }

    /**
     * @final
     *
     * @param positive-int $count
     * @param Parameters   $criteria
     *
     * @return T[]
     */
    public static function randomSet(int $count, array $criteria = []): array
    {
        return static::repository()->randomSet($count, $criteria);
    }

    /**
     * @final
     *
     * @param int<0, max> $min
     * @param int<0, max> $max
     * @param Parameters  $criteria
     *
     * @return T[]
     */
    public static function randomRange(int $min, int $max, array $criteria = []): array
    {
        return static::repository()->randomRange($min, $max, $criteria);
    }

    /**
     * @final
     *
     * @param Parameters $criteria
     *
     * @return T[]
     */
    public static function findBy(array $criteria): array
    {
        return static::repository()->findBy($criteria);
    }

    /**
     * @final
     *
     * @param Parameters $criteria
     *
     * @return T
     */
    public static function random(array $criteria = []): object
    {
        return static::repository()->random($criteria);
    }

    /**
     * @final
     *
     * @return T
     *
     * @throws \RuntimeException If no objects exist
     */
    public static function first(string $sortBy = 'id'): object
    {
        return static::repository()->firstOrFail($sortBy);
    }

    /**
     * @final
     *
     * @return T
     *
     * @throws \RuntimeException If no objects exist
     */
    public static function last(string $sortBy = 'id'): object
    {
        return static::repository()->lastOrFail($sortBy);
    }

    /**
     * @final
     *
     * @return T[]
     */
    public static function all(): array
    {
        return static::repository()->findAll();
    }

    /**
     * @final
     *
     * @return RepositoryDecorator<T,ObjectRepository<T>>
     */
    public static function repository(): ObjectRepository
    {
        Configuration::instance()->assertPersistanceEnabled();

        return new RepositoryDecorator(static::class()); // @phpstan-ignore-line
    }

    final public static function assert(): RepositoryAssertions
    {
        return static::repository()->assert();
    }

    /**
     * @param Parameters $criteria
     */
    final public static function count(array $criteria = []): int
    {
        return static::repository()->count($criteria);
    }

    final public static function truncate(): void
    {
        static::repository()->truncate();
    }

    final public function create(callable|array $attributes = []): object
    {
        $object = parent::create($attributes);

        if (!$this->isPersisting()) {
            return $this->proxy($object);
        }

        $configuration = Configuration::instance();

        if (!$configuration->isPersistenceAvailable()) {
            throw new \LogicException('Persistence cannot be used in unit tests.');
        }

        $configuration->persistence()->save($object);

        foreach ($this->tempAfterPersist as $callback) {
            $callback($object);
        }

        $this->tempAfterPersist = [];

        if ($this->afterPersist) {
            $attributes = $this->normalizeAttributes($attributes);

            foreach ($this->afterPersist as $callback) {
                $callback($object, $attributes);
            }

            $configuration->persistence()->save($object);
        }

        return $this->proxy($object);
    }

    final public function andPersist(): static
    {
        $clone = clone $this;
        $clone->persist = true;

        return $clone;
    }

    final public function withoutPersisting(): static
    {
        $clone = clone $this;
        $clone->persist = false;

        return $clone;
    }

    /**
     * @param callable(T, Parameters):void $callback
     */
    final public function afterPersist(callable $callback): static
    {
        $clone = clone $this;
        $clone->afterPersist[] = $callback;

        return $clone;
    }

    protected function normalizeParameter(string $field, mixed $value): mixed
    {
        if (!Configuration::instance()->isPersistenceAvailable()) {
            return unproxy(parent::normalizeParameter($field, $value));
        }

        if ($value instanceof self && isset($this->persist)) {
            $value->persist = $this->persist; // todo - breaks immutability
        }

        if ($value instanceof self && Configuration::instance()->persistence()->relationshipMetadata(static::class(), $value::class(), $field)?->isCascadePersist) {
            $value->persist = false;
        }

        return unproxy(parent::normalizeParameter($field, $value));
    }

    protected function normalizeCollection(string $field, FactoryCollection $collection): array
    {
        if (!$this->isPersisting() || !$collection->factory instanceof self) {
            return parent::normalizeCollection($field, $collection);
        }

        $pm = Configuration::instance()->persistence();

        if ($inverseField = $pm->relationshipMetadata($collection->factory::class(), static::class(), $field)?->inverseField) {
            $this->tempAfterPersist[] = static function(object $object) use ($collection, $inverseField, $pm) {
                $collection->create([$inverseField => $object]);
                $pm->refresh($object);
            };

            // creation delegated to afterPersist hook - return empty array here
            return [];
        }

        return parent::normalizeCollection($field, $collection);
    }

    /**
     * @internal
     */
    protected function normalizeObject(object $object): object
    {
        $reflectionClass = new \ReflectionClass($object::class);

        if ($reflectionClass->isFinal()) {
            return $object;
        }

        // readonly classes exist since php 8.2 and proxyHelper supports them since 8.3
        if (80200 <= \PHP_VERSION_ID && \PHP_VERSION_ID < 80300 && $reflectionClass->isReadonly()) {
            return $object;
        }

        $configuration = Configuration::instance();

        if (!$configuration->isPersistenceAvailable() || !$configuration->persistence()->hasPersistenceFor($object)) {
            return $object;
        }

        try {
            return proxy($object)->_refresh()->_real();
        } catch (RefreshObjectFailed|VarExportLogicException) {
            return $object;
        }
    }

    final protected function isPersisting(): bool
    {
        $config = Configuration::instance();

        if ($config->isPersistenceAvailable() && !$config->persistence()->isEnabled()) {
            return false;
        }

        return $this->persist ?? $config->isPersistenceAvailable() && $config->persistence()->isEnabled() && $config->persistence()->autoPersist(static::class());
    }

    /**
     * @param T $object
     *
     * @return T
     */
    private function proxy(object $object): object
    {
        if (!$this instanceof PersistentProxyObjectFactory) {
            return $object;
        }

        $object = proxy($object);

        return $this->isPersisting() ? $object : $object->_disableAutoRefresh();
    }
}
