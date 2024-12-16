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

use function Zenstruck\Foundry\get;

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

    /** @phpstan-var list<callable(T, Parameters, static):void> */
    private array $afterPersist = [];

    /** @var list<callable(T):void> */
    private array $tempAfterPersist = [];

    /**
     * @phpstan-param mixed|Parameters $criteriaOrId
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
     * @phpstan-param Parameters $criteria
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
     * @phpstan-param Parameters $criteria
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
     * @param positive-int $count
     * @phpstan-param Parameters   $criteria
     *
     * @return list<T>
     */
    public static function randomSet(int $count, array $criteria = []): array
    {
        return static::repository()->randomSet($count, $criteria);
    }

    /**
     * @param int<0, max> $min
     * @param int<0, max> $max
     * @phpstan-param Parameters  $criteria
     *
     * @return list<T>
     */
    public static function randomRange(int $min, int $max, array $criteria = []): array
    {
        return static::repository()->randomRange($min, $max, $criteria);
    }

    /**
     * @phpstan-param Parameters $criteria
     *
     * @return list<T>
     */
    public static function findBy(array $criteria): array
    {
        return static::repository()->findBy($criteria);
    }

    /**
     * @phpstan-param Parameters $criteria
     *
     * @return T
     */
    public static function random(array $criteria = []): object
    {
        return static::repository()->random($criteria);
    }

    /**
     * @return T
     *
     * @throws \RuntimeException If no objects exist
     */
    public static function first(string $sortBy = 'id'): object
    {
        /** @var T $object */
        $object = static::repository()->firstOrFail($sortBy);

        return $object;
    }

    /**
     * @return T
     *
     * @throws \RuntimeException If no objects exist
     */
    public static function last(string $sortBy = 'id'): object
    {
        /** @var T $object */
        $object = static::repository()->lastOrFail($sortBy);

        return $object;
    }

    /**
     * @return list<T>
     */
    public static function all(): array
    {
        return static::repository()->findAll();
    }

    /**
     * @return RepositoryDecorator<T,ObjectRepository<T>>
     */
    public static function repository(): ObjectRepository
    {
        Configuration::instance()->assertPersistenceEnabled();

        return new RepositoryDecorator(static::class()); // @phpstan-ignore return.type
    }

    final public static function assert(): RepositoryAssertions
    {
        return static::repository()->assert();
    }

    /**
     * @phpstan-param Parameters $criteria
     */
    final public static function count(array $criteria = []): int
    {
        return static::repository()->count($criteria);
    }

    final public static function truncate(): void
    {
        static::repository()->truncate();
    }

    /**
     * @return T
     */
    public function create(callable|array $attributes = []): object
    {
        $object = parent::create($attributes);

        $this->throwIfCannotCreateObject();

        if (!$this->isPersisting()) {
            return $object;
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
            $attributes = $this->normalizedParameters ?? throw new \LogicException('Factory::$normalizedParameters has not been initialized.');

            foreach ($this->afterPersist as $callback) {
                $callback($object, $attributes, $this);
            }

            $configuration->persistence()->save($object);
        }

        return $object;
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
     * @phpstan-param callable(T, Parameters, static):void $callback
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
            $value = $this->isPersisting()
                ? $value->andPersist()
                : $value->withoutPersisting();
        }

        if ($value instanceof self) {
            $pm = Configuration::instance()->persistence();

            $inversedRelationshipMetadata = $pm->inverseRelationshipMetadata(static::class(), $value::class(), $field);

            // handle inversed OneToOne
            if ($inversedRelationshipMetadata && !$inversedRelationshipMetadata->isCollection) {
                $inverseField = $inversedRelationshipMetadata->inverseField;

                // we create now the object to prevent "non-nullable" property errors,
                // but we'll need to remove it once the current object is created

                $inversedObject = unproxy($value->create());
                $this->tempAfterPersist[] = static function(object $object) use ($value, $inverseField, $pm, $inversedObject) {
                    // we cannot use the already created $inversedObject:
                    // because we must also remove its potential newly created owner (here: "$oldObj")
                    // but a cascade:["remove"] would remove too many things
                    $value->create([$inverseField => $object]);
                    $pm->refresh($object);
                    $oldObj = get($inversedObject, $inverseField);
                    delete($inversedObject);
                    if ($oldObj) {
                        delete($oldObj); // @phpstan-ignore argument.templateType
                    }
                };

                return $inversedObject;
            }
        }

        return unproxy(parent::normalizeParameter($field, $value));
    }

    protected function normalizeCollection(string $field, FactoryCollection $collection): array
    {
        if (!$this->isPersisting() || !$collection->factory instanceof self) {
            return parent::normalizeCollection($field, $collection);
        }

        $pm = Configuration::instance()->persistence();

        $inverseRelationshipMetadata = $pm->inverseRelationshipMetadata(static::class(), $collection->factory::class(), $field);

        if ($inverseRelationshipMetadata && $inverseRelationshipMetadata->isCollection) {
            $inverseField = $inverseRelationshipMetadata->inverseField;

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
     * Schedule any new object for insert right after instantiation.
     */
    final protected function initializeInternal(): static
    {
        return $this->afterInstantiate(
            static function(object $object, array $parameters, PersistentObjectFactory $factory): void {
                if (!$factory->isPersisting()) {
                    return;
                }

                Configuration::instance()->persistence()->scheduleForInsert($object);
            }
        );
    }

    private function throwIfCannotCreateObject(): void
    {
        $configuration = Configuration::instance();

        /**
         * "false === $configuration->inADataProvider()" would also mean that the PHPUnit extension is NOT used
         * so a `FoundryNotBooted` exception would be thrown if we actually are in a data provider.
         */
        if (!$configuration->inADataProvider()) {
            return;
        }

        if (
            !$configuration->isPersistenceAvailable()
            || $this instanceof PersistentProxyObjectFactory
        ) {
            return;
        }

        throw new \LogicException(\sprintf('Cannot create object in a data provider for non-proxy factories. Transform your factory into a "%s", or call "create()" method in the test. See https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#phpunit-data-providers', PersistentProxyObjectFactory::class));
    }
}
