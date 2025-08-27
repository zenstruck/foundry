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
use Zenstruck\Foundry\Exception\FoundryNotBooted;
use Zenstruck\Foundry\Exception\PersistenceDisabled;
use Zenstruck\Foundry\Exception\PersistenceNotAvailable;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Object\Hydrator;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\Exception\NotEnoughObjects;
use Zenstruck\Foundry\Persistence\Exception\RefreshObjectFailed;
use Zenstruck\Foundry\Persistence\Relationship\ManyToOneRelationship;
use Zenstruck\Foundry\Persistence\Relationship\OneToManyRelationship;
use Zenstruck\Foundry\Persistence\Relationship\OneToOneRelationship;

use function Zenstruck\Foundry\force;
use function Zenstruck\Foundry\get;
use function Zenstruck\Foundry\set;

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
    private PersistMode $persist = PersistMode::PERSIST;

    /** @phpstan-var list<callable(T, Parameters, static):void> */
    private array $afterPersist = [];

    /** @var list<callable(T):void> */
    private array $tempAfterInstantiate = [];

    private bool $isRootFactory = true;

    private bool $autorefreshEnabled = false;

    public function __construct()
    {
        parent::__construct();

        $this->autorefreshEnabled = Configuration::autoRefreshWithLazyObjectsIsEnabled();
    }

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
     * @param int<0, max> $min
     * @param int<0, max> $max
     * @phpstan-param Parameters  $criteria
     *
     * @return list<T>
     */
    public static function randomRangeOrCreate(int $min, int $max, array $criteria = []): array
    {
        $targetCount = \mt_rand($min, $max);

        try {
            return static::repository()->randomRange($min, $targetCount, $criteria);
        } catch (NotEnoughObjects) {
            $foundObjects = static::repository()->findBy($criteria);
        } catch (PersistenceNotAvailable|PersistenceDisabled) {
            $foundObjects = [];
        }

        $objectsToCreate = $targetCount - \count($foundObjects);

        $newObjects = static::createMany($objectsToCreate, $criteria);

        return [...$foundObjects, ...$newObjects];
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

        return new RepositoryDecorator(static::class(), Configuration::instance()->isInMemoryEnabled()); // @phpstan-ignore return.type
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
        $configuration = Configuration::instance();

        if ($configuration->inADataProvider()
            && \PHP_VERSION_ID >= 80400
            && $this->isPersisting()
            && !$this instanceof PersistentProxyObjectFactory
        ) {
            return ProxyGenerator::wrapFactoryNativeProxy($this, $attributes);
        }

        $object = parent::create($attributes);

        foreach ($this->tempAfterInstantiate as $callback) {
            $callback($object);
        }

        $this->tempAfterInstantiate = [];

        $this->throwIfCannotCreateObject();

        if (PersistMode::PERSIST !== $this->persistMode()) {
            return $object;
        }

        if ($configuration->flushOnce && !$this->isRootFactory) {
            return $object;
        }

        if (!$configuration->isPersistenceAvailable()) {
            throw new \LogicException('Persistence cannot be used in unit tests.');
        }

        $configuration->persistence()->save($object);

        return $object;
    }

    final public function andPersist(): static
    {
        $clone = clone $this;
        $clone->persist = PersistMode::PERSIST;

        return $clone;
    }

    final public function withoutPersisting(): static
    {
        $clone = clone $this;
        $clone->persist = PersistMode::WITHOUT_PERSISTING;

        return $clone;
    }

    final public function withAutorefresh(): static
    {
        if (\PHP_VERSION_ID < 80400) {
            throw new \LogicException('Auto-refresh requires PHP 8.4 or higher.');
        }

        $clone = clone $this;
        $clone->autorefreshEnabled = true;

        return $clone;
    }

    final public function withoutAutorefresh(): static
    {
        if (\PHP_VERSION_ID < 80400) {
            throw new \LogicException('Auto-refresh requires PHP 8.4 or higher.');
        }

        $clone = clone $this;
        $clone->autorefreshEnabled = false;

        return $clone;
    }

    /**
     * @internal
     */
    public function withPersistMode(PersistMode $persistMode): static
    {
        $clone = clone $this;
        $clone->persist = $persistMode;

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

    /**
     * @internal
     */
    public function persistMode(): PersistMode
    {
        return $this->isPersistenceEnabled() && !$this->isInMemoryEnabled() ? $this->persist : PersistMode::WITHOUT_PERSISTING;
    }

    final public function isPersisting(): bool
    {
        return $this->persistMode()->isPersisting();
    }

    /**
     * @internal
     */
    public function notRootFactory(): static
    {
        $clone = clone $this;
        $clone->isRootFactory = false;

        return $clone;
    }

    protected function normalizeParameter(string $field, mixed $value): mixed
    {
        if (!Configuration::instance()->isPersistenceAvailable()) {
            return ProxyGenerator::unwrap(parent::normalizeParameter($field, $value));
        }

        if ($value instanceof self) {
            $value = $value
                ->withPersistMode($this->persist)
                ->notRootFactory();

            $pm = Configuration::instance()->persistence();

            $relationshipMetadata = $pm->bidirectionalRelationshipMetadata(static::class(), $value::class(), $field);

            // handle inverse OneToOne
            if ($relationshipMetadata instanceof OneToOneRelationship && !$relationshipMetadata->isOwning) {
                $inverseField = $relationshipMetadata->inverseField();

                $value = $value
                    ->reuse(...$this->reusedObjects(), ...$value->reusedObjects())
                    ->withPersistMode(
                        $this->isPersisting() ? PersistMode::NO_PERSIST_BUT_SCHEDULE_FOR_INSERT : PersistMode::WITHOUT_PERSISTING
                    )
                ;

                if (($fieldType = (new \ReflectionClass(static::class()))->getProperty($field)->getType())?->allowsNull()) {
                    $this->tempAfterInstantiate[] = static function(object $object) use ($value, $inverseField, $field) {
                        $inverseObject = $value->create([$inverseField => $object]);

                        set($object, $field, ProxyGenerator::unwrap($inverseObject, withAutoRefresh: false));
                    };

                    // we're using "force" here to avoid a potential type check in a setter
                    return force(null);
                } elseif (($inverseFieldType = (new \ReflectionClass($value::class()))->getProperty($inverseField)->getType())?->allowsNull()) {
                    $inverseObject = ProxyGenerator::unwrap(
                        // we're using "force" here to avoid a potential type check in a setter
                        $value->create([$inverseField => force(null)]),
                        withAutoRefresh: false
                    );

                    $this->tempAfterInstantiate[] = static function(object $object) use ($inverseObject, $inverseField) {
                        set($inverseObject, $inverseField, $object);
                    };

                    return $inverseObject;
                } elseif (null === $fieldType || null === $inverseFieldType) {
                    throw new \InvalidArgumentException(\sprintf("Cannot handle inverse OneToOne relationship: cannot determine types of \"%s::\${$field}\" and \"%s::\${$inverseField}\", please and type to the properties.", static::class(), $value::class()));
                } else {
                    throw new \InvalidArgumentException(\sprintf("Cannot handle inverse OneToOne relationship: both \"%s::\${$field}\" and \"%s::\${$inverseField}\" are not nullable, which will result in a circular dependency.", static::class(), $value::class()));
                }
            }
        }

        return ProxyGenerator::unwrap(parent::normalizeParameter($field, $value), withAutoRefresh: false);
    }

    protected function normalizeCollection(string $field, FactoryCollection $collection): array
    {
        if (!Configuration::instance()->isPersistenceAvailable() || !$collection->factory instanceof self) {
            return parent::normalizeCollection($field, $collection);
        }

        $pm = Configuration::instance()->persistence();

        $inverseRelationshipMetadata = $pm->bidirectionalRelationshipMetadata(static::class(), $collection->factory::class(), $field);

        $collection = $collection->notRootFactory();

        if ($inverseRelationshipMetadata instanceof OneToManyRelationship) {
            $this->tempAfterInstantiate[] = function(object $object) use ($collection, $inverseRelationshipMetadata, $field) {
                $inverseField = $inverseRelationshipMetadata->inverseField();

                $inverseObjects = $collection
                    ->reuse(...$this->reusedObjects())
                    ->withPersistMode($this->isPersisting() ? PersistMode::NO_PERSIST_BUT_SCHEDULE_FOR_INSERT : PersistMode::WITHOUT_PERSISTING)
                    ->create([$inverseField => $object]);

                $inverseObjects = ProxyGenerator::unwrap($inverseObjects, withAutoRefresh: false);

                // if the collection is indexed by a field, index the array
                if ($inverseRelationshipMetadata->collectionIndexedBy) {
                    $inverseObjects = \array_combine(
                        \array_map(static fn($o) => get($o, $inverseRelationshipMetadata->collectionIndexedBy), $inverseObjects),
                        \array_values($inverseObjects)
                    );
                }

                set($object, $field, $inverseObjects);
            };

            // creation delegated to tempAfterInstantiate hook - return empty array here
            return [];
        }

        return parent::normalizeCollection($field, $collection);
    }

    /**
     * This method will try to find entities in database if they are detached.
     *
     * @internal
     */
    protected function normalizeObject(string $field, object $object): object
    {
        $configuration = Configuration::instance();

        $object = ProxyGenerator::unwrap($object, withAutoRefresh: false);

        if (!$configuration->isPersistenceAvailable()) {
            return $object;
        }

        $persistenceManager = $configuration->persistence();

        if (!$persistenceManager->hasPersistenceFor($object)) {
            return $object;
        }

        $inverseRelationship = $persistenceManager->bidirectionalRelationshipMetadata(static::class(), $object::class, $field);

        if ($inverseRelationship instanceof OneToOneRelationship) {
            $this->tempAfterInstantiate[] = static function(object $newObject) use ($object, $inverseRelationship) {
                Hydrator::set($object, $inverseRelationship->inverseField(), $newObject, catchErrors: true);
            };
        }

        if ($inverseRelationship instanceof ManyToOneRelationship) {
            $this->tempAfterInstantiate[] = static function(object $newObject) use ($object, $inverseRelationship) {
                Hydrator::add($object, $inverseRelationship->inverseField(), $newObject);
            };
        }

        if (
            !$this->isPersisting()
        ) {
            return $object;
        }

        if (!$persistenceManager->isPersisted($object)) {
            $persistenceManager->scheduleForInsert($object);

            return $object;
        }

        try {
            return $configuration->persistence()->refresh($object);
        } catch (RefreshObjectFailed|VarExportLogicException) { // @phpstan-ignore catch.neverThrown (thrown by var exporter)
            return $object;
        }
    }

    final protected function initializeInternal(): static
    {
        // Schedule any new object for insert right after instantiation
        return parent::initializeInternal()
            ->afterInstantiate(
                static function(object $object, array $parameters, PersistentObjectFactory $factoryUsed): void {
                    if (!$factoryUsed->isPersisting()) {
                        return;
                    }

                    if (
                        $factoryUsed->autorefreshEnabled
                        && !$factoryUsed instanceof PersistentProxyObjectFactory
                    ) {
                        Configuration::instance()->persistedObjectsTracker?->add($object);
                    }

                    $afterPersistCallbacks = [];

                    foreach ($factoryUsed->afterPersist as $afterPersist) {
                        $afterPersistCallbacks[] = static function() use ($object, $afterPersist, $parameters, $factoryUsed): void {
                            $afterPersist($object, $parameters, $factoryUsed);
                        };
                    }

                    Configuration::instance()->persistence()->scheduleForInsert($object, $afterPersistCallbacks);
                }
            )
        ;
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
            $this instanceof PersistentProxyObjectFactory
            || !$this->isPersisting()
        ) {
            return;
        }

        throw new \LogicException(\sprintf('Cannot create object in a data provider for non-proxy factories. Transform your factory into a "%s", or call "create()" method in the test. See https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#phpunit-data-providers', PersistentProxyObjectFactory::class));
    }

    private function isPersistenceEnabled(): bool
    {
        try {
            return Configuration::instance()->isPersistenceEnabled();
        } catch (FoundryNotBooted) {
            return false;
        }
    }

    private function isInMemoryEnabled(): bool
    {
        try {
            return Configuration::instance()->isInMemoryEnabled();
        } catch (FoundryNotBooted) {
            return false;
        }
    }
}
