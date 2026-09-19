<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Persistence\BulkInserter;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\PersistMode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T
 * @template TFactory of Factory<T>
 * @implements \IteratorAggregate<TFactory>
 *
 * @phpstan-import-type Attributes from Factory
 * @phpstan-import-type Parameters from Factory
 */
final class FactoryCollection implements \IteratorAggregate
{
    private PersistMode $persistMode;
    private bool $isRootFactory = true;
    private bool $collectResults = true;

    /**
     * @param TFactory $factory
     * @phpstan-param \Closure():iterable<Attributes>|\Closure():iterable<TFactory> $items
     */
    private function __construct(public readonly Factory $factory, private \Closure $items)
    {
        $this->persistMode = $this->factory instanceof PersistentObjectFactory
            ? $this->factory->persistMode()
            : PersistMode::WITHOUT_PERSISTING;
    }

    /**
     * @internal
     */
    public function withPersistMode(PersistMode $persistMode): static
    {
        $clone = clone $this;
        $clone->persistMode = $persistMode;

        return $clone;
    }

    /**
     * @internal
     * @return self<T, TFactory>
     */
    public function notRootFactory(): static
    {
        $clone = clone $this;
        $clone->isRootFactory = false;

        return $clone;
    }

    /**
     * @phpstan-assert-if-true non-empty-list<TFactory> $potentialFactories
     *
     * @internal
     */
    public static function accepts(mixed $potentialFactories): bool
    {
        if (!\is_array($potentialFactories) || 0 === \count($potentialFactories) || !\array_is_list($potentialFactories)) {
            return false;
        }

        if (!$potentialFactories[0] instanceof ObjectFactory) {
            return false;
        }

        foreach ($potentialFactories as $potentialFactory) {
            if (!$potentialFactory instanceof ObjectFactory
                || $potentialFactories[0]::class() !== $potentialFactory::class()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<TFactory> $factories
     *
     * @return self<T, TFactory>
     *
     * @internal
     */
    public static function fromFactoriesList(array $factories): self
    {
        if (!self::accepts($factories)) {
            throw new \InvalidArgumentException('All factories must be of the same type.');
        }

        return new self($factories[0], static fn() => $factories);
    }

    /**
     * @param TFactory $factory
     *
     * @return self<T, TFactory>
     */
    public static function many(Factory $factory, int $count): self
    {
        return new self($factory, static fn() => \array_fill(0, $count, []));
    }

    /**
     * @param TFactory $factory
     *
     * @return self<T, TFactory>
     */
    public static function range(Factory $factory, int $min, int $max): self
    {
        if ($min > $max) {
            throw new \InvalidArgumentException('Min must be less than max.');
        }

        return new self($factory, static fn() => \array_fill(0, \mt_rand($min, $max), []));
    }

    /**
     * @param TFactory $factory
     * @phpstan-param  iterable<Attributes> $items
     * @return self<T, TFactory>
     */
    public static function sequence(Factory $factory, iterable $items): self
    {
        $collection = new self($factory, static fn() => $items);
        $collection->collectResults = false;

        return $collection;
    }

    /**
     * Creates the objects once, and returns the same ones every time the value is used.
     */
    public function memoize(): LazyValue
    {
        return LazyValue::memoizeFromFactory($this);
    }

    /**
     * @phpstan-param Attributes $attributes
     *
     * @return list<T>
     */
    public function create(array|callable $attributes = []): array
    {
        if ($this->canBulkCreate()) {
            return $this->bulkCreate($attributes);
        }

        $factories = $this->all();

        if (Configuration::instance()->flushOnce && $this->isRootFactory && $this->factory instanceof PersistentObjectFactory && $this->factory->isPersisting()) {
            $lastFactory = \array_pop($factories);
            // @phpstan-ignore method.notFound (phpstan does not understand that we only have persistent factories here)
            $factories = \array_map(static fn(Factory $f) => $f->notRootFactory(), $factories);

            if (null !== $lastFactory) {
                $factories[] = $lastFactory;
            }
        }

        return \array_map(static fn(Factory $f) => $f->create($attributes), $factories);
    }

    /**
     * Create objects via a fast pipeline and persist them using
     * multi-row INSERT or COPY (bypassing the ORM UnitOfWork).
     *
     * Handles batching, EM clearing, and memory management automatically.
     * FK dependencies between created entities are resolved per batch.
     *
     * @phpstan-param Attributes $attributes
     *
     * @return list<T>
     */
    public function bulkCreate(array|callable $attributes = [], int $batchSize = 10_000): array
    {
        $configuration = Configuration::instance();

        if (!$configuration->isPersistenceAvailable() || !$this->factory instanceof PersistentObjectFactory) {
            return $this->create($attributes);
        }

        $om = $configuration->persistence()->objectManagerFor($this->factory::class());

        if (!$om instanceof EntityManagerInterface) {
            return $this->create($attributes);
        }

        $meta = self::getFactoryMeta($this->factory);
        $defaults = $meta['defaults']->invoke($this->factory);
        $class = $meta['class'];

        $hookFactory = self::nonPersistingFactory($this->factory);
        $hooks = self::getAfterInstantiateHooks($hookFactory);

        $attrMap = BulkInserter::getAttributeColumnMap($om, $class);
        // Framework registers 3 afterInstantiate hooks (ObjectFactory:1 + PersistentObjectFactory:2)
        $hasUserAfterInstantiateHooks = \count($hooks) > 3;
        $afterPersistHooks = self::getAfterPersistHooks($hookFactory);
        $useDirectInsert = null !== $attrMap && !$hasUserAfterInstantiateHooks && [] === $afterPersistHooks;

        $connection = $om->getConnection();
        $droppedIndices = self::dropNonEssentialIndices($connection, $om, $class);
        $unloggedTables = self::setUnloggedTables($connection, $om, $class);
        self::tuneForBulk($connection);
        $connection->beginTransaction();
        \gc_disable();

        try {
            if ($useDirectInsert) {
                $allObjects = self::directInsertLoop($om, $connection, $this->items, $defaults, $attributes, $class, $attrMap, $batchSize);
            } else {
                $allObjects = self::objectInsertLoop($om, $this->items, $defaults, $attributes, $class, $meta['instantiator'], $hooks, $hookFactory, $afterPersistHooks, $batchSize, $this->collectResults);
            }

            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();

            throw $e;
        } finally {
            \gc_enable();
            self::restoreIndices($connection, $droppedIndices);
            self::restoreLoggedTables($connection, $unloggedTables);
        }

        return $allObjects;
    }

    /**
     * @return list<object>
     */
    private static function objectInsertLoop(
        EntityManagerInterface $om,
        \Closure $items,
        array $defaults,
        array|callable $attributes,
        string $class,
        callable $instantiator,
        array $hooks,
        Factory $hookFactory,
        array $afterPersistHooks,
        int $batchSize,
        bool $collectResults = true,
    ): array {
        $allObjects = [];
        $batch = [];
        $index = 0;
        $isCallable = \is_callable($attributes);

        foreach ($items() as $itemAttributes) {
            ++$index;

            if ($itemAttributes instanceof Factory) {
                $obj = $itemAttributes instanceof PersistentObjectFactory
                    ? $itemAttributes->withPersistMode(PersistMode::WITHOUT_PERSISTING)->create($attributes)
                    : $itemAttributes->create($attributes);
                $batch[] = $obj;
            } else {
                $merged = $defaults;
                if (\is_array($itemAttributes) && [] !== $itemAttributes) {
                    foreach ($itemAttributes as $k => $v) {
                        $merged[$k] = $v;
                    }
                }
                if ($isCallable) {
                    $extra = $attributes($index);
                    if ([] !== $extra) {
                        foreach ($extra as $k => $v) {
                            $merged[$k] = $v;
                        }
                    }
                } elseif ([] !== $attributes) {
                    foreach ($attributes as $k => $v) {
                        $merged[$k] = $v;
                    }
                }

                self::fastResolve($merged);

                $obj = $instantiator($merged, $class);
                foreach ($hooks as $hook) {
                    $hook($obj, $merged, $hookFactory);
                }

                $batch[] = $obj;
            }

            if (\count($batch) >= $batchSize) {
                BulkInserter::bulkInsertObjectGraph($om, $batch);

                if ([] !== $afterPersistHooks) {
                    self::runAfterPersistBulk($om, $batch, $afterPersistHooks, $hookFactory);
                }

                if ($collectResults) {
                    self::lightenBatch($om, $batch, $class);
                    \array_push($allObjects, ...$batch);
                }

                $batch = [];
                $om->clear();
                \gc_enable();
                \gc_collect_cycles();
                \gc_disable();
            }
        }

        if ([] !== $batch) {
            BulkInserter::bulkInsertObjectGraph($om, $batch);

            if ([] !== $afterPersistHooks) {
                self::runAfterPersistBulk($om, $batch, $afterPersistHooks, $hookFactory);
            }

            if ($collectResults) {
                self::lightenBatch($om, $batch, $class);
                \array_push($allObjects, ...$batch);
            }
        }

        return $allObjects;
    }

    /**
     * Fast path: convert attributes directly to DB rows, skipping object creation.
     *
     * @return list<object>
     */
    private static function directInsertLoop(
        EntityManagerInterface $om,
        Connection $connection,
        \Closure $items,
        array $defaults,
        array|callable $attributes,
        string $class,
        array $attrMap,
        int $batchSize,
    ): array {
        /** @var \Doctrine\ORM\Mapping\ClassMetadata<object> $metadata */
        $metadata = $om->getClassMetadata($class);
        $tableName = $metadata->getTableName();

        $rows = [];
        $index = 0;
        $totalInserted = 0;

        foreach ($items() as $itemAttributes) {
            ++$index;

            $merged = $defaults;

            if ($itemAttributes instanceof Factory) {
                continue;
            }

            if (\is_array($itemAttributes) && [] !== $itemAttributes) {
                $merged = \array_replace($merged, $itemAttributes);
            }
            $extra = \is_callable($attributes) ? $attributes($index) : $attributes;
            if ([] !== $extra) {
                $merged = \array_replace($merged, $extra);
            }

            self::fastResolve($merged);

            $rows[] = BulkInserter::attributesToRow($merged, $attrMap);

            if (\count($rows) >= $batchSize) {
                BulkInserter::insertBatch($connection, $tableName, $rows, $attrMap['types']);
                $totalInserted += \count($rows);
                $rows = [];
            }
        }

        if ([] !== $rows) {
            BulkInserter::insertBatch($connection, $tableName, $rows, $attrMap['types']);
            $totalInserted += \count($rows);
        }

        return [];
    }

    /**
     * Replace heavy objects with ID-only lightweight instances to save memory.
     *
     * @param list<object> $batch
     */
    private static function lightenBatch(EntityManagerInterface $om, array &$batch, string $class): void
    {
        if (!$om->getMetadataFactory()->hasMetadataFor($class)) {
            return;
        }

        /** @var \Doctrine\ORM\Mapping\ClassMetadata<object> $metadata */
        $metadata = $om->getClassMetadata($class);
        $idField = $metadata->getSingleIdentifierFieldName();
        $reflId = new \ReflectionProperty($class, $idField);
        $reflClass = new \ReflectionClass($class);

        foreach ($batch as $i => $obj) {
            $id = $reflId->getValue($obj);

            if (null !== $id) {
                $ref = $reflClass->newInstanceWithoutConstructor();
                $reflId->setValue($ref, $id);
                $batch[$i] = $ref;
            }
        }
    }

    /**
     * @var array<class-string, array{defaults: \ReflectionMethod, instantiator: callable, class: class-string}>
     */
    private static array $factoryCache = [];
    private static ?\ReflectionProperty $afterInstantiateRef = null;

    /**
     * @return array{defaults: \ReflectionMethod, instantiator: callable, class: class-string}
     */
    private static function getFactoryMeta(Factory $factory): array
    {
        $key = $factory::class;

        return self::$factoryCache[$key] ??= [
            'defaults' => new \ReflectionMethod($factory, 'defaults'),
            'instantiator' => $factory->instantiator(),
            'class' => $factory::class(),
        ];
    }

    /**
     * @return list<callable>
     */
    private static function getAfterInstantiateHooks(Factory $factory): array
    {
        self::$afterInstantiateRef ??= new \ReflectionProperty(ObjectFactory::class, 'afterInstantiate');

        return \array_merge(...self::$afterInstantiateRef->getValue($factory));
    }

    private function canBulkCreate(): bool
    {
        if (!$this->factory instanceof PersistentObjectFactory || !$this->factory->isPersisting()) {
            return false;
        }

        $configuration = Configuration::instance();

        if (!$configuration->isPersistenceAvailable()) {
            return false;
        }

        return $configuration->persistence()->objectManagerFor($this->factory::class()) instanceof EntityManagerInterface;
    }

    private static ?\ReflectionProperty $afterPersistRef = null;

    /**
     * @return list<callable>
     */
    private static function getAfterPersistHooks(Factory $factory): array
    {
        if (!$factory instanceof PersistentObjectFactory) {
            return [];
        }

        self::$afterPersistRef ??= new \ReflectionProperty(PersistentObjectFactory::class, 'afterPersist');
        $prioritized = self::$afterPersistRef->getValue($factory);

        if ([] === $prioritized) {
            return [];
        }

        $hooks = \array_merge(...$prioritized);

        // Framework registers 1 afterPersist hook (AfterPersist event dispatch).
        // Only user-defined hooks matter for bulk mode.
        return \count($hooks) > 1 ? $hooks : [];
    }

    /**
     * Run afterPersist hooks on a batch, bulk-inserting any entities they create.
     *
     * @param list<object>    $batch
     * @param list<callable>  $afterPersistHooks
     */
    private static function runAfterPersistBulk(EntityManagerInterface $om, array $batch, array $afterPersistHooks, Factory $hookFactory): void
    {
        PersistentObjectFactory::enableBulkBuffering();

        foreach ($batch as $entity) {
            foreach ($afterPersistHooks as $hook) {
                $hook($entity, [], $hookFactory);
            }
        }

        $buffered = PersistentObjectFactory::flushBulkBuffer();

        if ([] !== $buffered) {
            BulkInserter::bulkInsertObjectGraph($om, $buffered);
        }
    }

    private static function nonPersistingFactory(Factory $factory): Factory
    {
        return $factory instanceof PersistentObjectFactory
            ? $factory->withPersistMode(PersistMode::WITHOUT_PERSISTING)
            : $factory;
    }

    private static function fastResolve(array &$attributes): void
    {
        foreach ($attributes as &$value) {
            if ($value instanceof LazyValue) {
                $value = $value();
            }
            if ($value instanceof Factory) {
                $value = self::fastCreateOne($value);
            }
            if ($value instanceof self) {
                $value = $value->create();
            }
            if (\is_array($value)) {
                self::fastResolve($value);
            }
        }
    }

    private static function fastCreateOne(Factory $factory): object
    {
        $meta = self::getFactoryMeta($factory);
        $defaults = $meta['defaults']->invoke($factory);

        self::fastResolve($defaults);

        $object = ($meta['instantiator'])($defaults, $meta['class']);

        $hookFactory = self::nonPersistingFactory($factory);

        foreach (self::getAfterInstantiateHooks($hookFactory) as $hook) {
            $hook($object, $defaults, $hookFactory);
        }

        return $object;
    }

    /**
     * @return list<TFactory>
     */
    public function all(): array
    {
        $factories = [];

        $i = 1;
        foreach (($this->items)() as $attributesOrFactory) {
            if ($attributesOrFactory instanceof Factory) {
                $factories[] = $attributesOrFactory;

                continue;
            }

            $factories[] = $this->factory->with($attributesOrFactory)->with(['__index' => $i++]);
        }

        return \array_map( // @phpstan-ignore return.type (PHPStan does not understand we have an array of factories)
            function(Factory $f) {
                if ($f instanceof PersistentObjectFactory) {
                    if (!$this->isRootFactory) {
                        $f = $f->notRootFactory();
                    }

                    return $f->withPersistMode($this->persistMode);
                }

                return $f;
            },
            $factories
        );
    }

    /**
     * @param list<mixed> $values
     *
     * @return self<T, TFactory>
     */
    public function distribute(string $field, array $values): self
    {
        $factories = $this->all();

        if (\count($factories) !== \count($values)) {
            throw new \InvalidArgumentException('Number of values must match number of factories.');
        }

        return new self(
            $this->factory,
            static fn() => \array_map(
                static fn(Factory $f, $value) => $f->with([$field => $value]),
                $factories,
                $values
            )
        );
    }

    /**
     * @param callable(TFactory):TFactory $callback
     *
     * @return self<T, TFactory>
     *
     * @internal
     */
    public function map(callable $callback): self
    {
        $factories = $this->all();

        if ([] === $factories) {
            return $this;
        }

        return new self($this->factory, static fn() => \array_map($callback, $factories));
    }

    /**
     * @internal
     */
    public function reuse(object ...$objects): static
    {
        if (0 === \count($objects)) {
            return $this;
        }

        return $this->map(static fn(Factory $f) => $f instanceof ObjectFactory ? $f->reuse(...$objects) : $f);
    }

    /**
     * @phpstan-param callable(int):mixed[] $attributes
     */
    public function applyStateMethod(string $stateMethodName, ?callable $attributes = null): static
    {
        $attributes ??= static fn(int $index) => [];

        try {
            $refectionMethod = new \ReflectionMethod($this->factory, $stateMethodName);
        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException(\sprintf('State method "%s" does not exist on factory "%s".', $stateMethodName, $this->factory::class), previous: $e);
        }

        if ($refectionMethod->isStatic()) {
            throw new \InvalidArgumentException(\sprintf('Method "%s::%s()" is static and cannot be used as a state method.', $this->factory::class, $stateMethodName));
        }

        $factories = $this->all();

        $stateMethodNumberOfRequiredParameters = $refectionMethod->getNumberOfRequiredParameters();
        $stateMethodNumberOfParameters = $refectionMethod->getNumberOfParameters();
        $stateMethodParameterNames = \array_map(static fn(\ReflectionParameter $p) => $p->getName(), $refectionMethod->getParameters());

        return new self(
            $this->factory,
            static fn() => \array_map(
                static function(Factory $f, int $index) use ($stateMethodName, $attributes, $stateMethodNumberOfRequiredParameters, $stateMethodNumberOfParameters, $stateMethodParameterNames) {
                    $parameters = $attributes($index + 1);

                    if (\count($parameters) < $stateMethodNumberOfRequiredParameters || \count($parameters) > $stateMethodNumberOfParameters) {
                        throw new \InvalidArgumentException(\sprintf('Invalid number of parameters for state method "%s::%s()".', $f::class, $stateMethodName));
                    }

                    if (!\array_is_list($parameters) && $extraParameters = \array_diff(\array_keys($parameters), $stateMethodParameterNames)) {
                        throw new \InvalidArgumentException(\sprintf('Parameter(s) "%s" don\'t exist for state method "%s::%s()".', \implode(',', $extraParameters), $f::class, $stateMethodName));
                    }

                    $newFactory = $f->{$stateMethodName}(...$parameters);

                    if ($newFactory::class !== $f::class) {
                        throw new \InvalidArgumentException(\sprintf('State method "%s::%s()" does not return a "%1$s".', $f::class, $stateMethodName));
                    }

                    return $newFactory;
                },
                $factories,
                \array_keys($factories),
            )
        );
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * @return iterable<array{TFactory}>
     */
    public function asDataProvider(): iterable
    {
        foreach ($this as $factory) {
            yield [$factory];
        }
    }

    /**
     * Drop non-PK, non-unique indices on the entity's table (and cascade tables) to speed up bulk inserts.
     *
     * @return list<array{name: string, def: string}>
     */
    private static function dropNonEssentialIndices(Connection $connection, EntityManagerInterface $om, string $class): array
    {
        try {
            $platform = $connection->getDatabasePlatform();
            if (!$platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        /** @var \Doctrine\ORM\Mapping\ClassMetadata<object> $metadata */
        $metadata = $om->getClassMetadata($class);
        $tables = ['"' . $metadata->getTableName() . '"'];

        foreach ($metadata->associationMappings as $assoc) {
            if ($assoc->isOwningSide() && isset($assoc->joinColumns)) {
                try {
                    $targetMeta = $om->getClassMetadata($assoc->targetEntity);
                    $tables[] = '"' . $targetMeta->getTableName() . '"';
                } catch (\Throwable) {
                }
            }
        }

        $tables = \array_unique($tables);
        $dropped = [];

        foreach ($tables as $table) {
            try {
                $indices = $connection->fetchAllAssociative(
                    "SELECT indexname, indexdef FROM pg_indexes WHERE schemaname = 'public' AND tablename = " . $connection->quote(\trim($table, '"'))
                    . " AND indexname NOT LIKE '%_pkey' AND indexdef NOT LIKE '%UNIQUE%'",
                );
            } catch (\Throwable) {
                continue;
            }

            foreach ($indices as $idx) {
                try {
                    $connection->executeStatement('DROP INDEX IF EXISTS "' . $idx['indexname'] . '"');
                    $dropped[] = ['name' => $idx['indexname'], 'def' => $idx['indexdef']];
                } catch (\Throwable) {
                }
            }
        }

        return $dropped;
    }

    /**
     * @param list<array{name: string, def: string}> $indices
     */
    private static function restoreIndices(Connection $connection, array $indices): void
    {
        foreach ($indices as $idx) {
            try {
                $connection->executeStatement($idx['def']);
            } catch (\Throwable) {
            }
        }
    }

    private static bool $bulkTuned = false;

    private static function tuneForBulk(Connection $connection): void
    {
        if (self::$bulkTuned) {
            return;
        }

        try {
            $platform = $connection->getDatabasePlatform();

            if (!$platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
                return;
            }

            $connection->executeStatement("SET synchronous_commit = 'off'");
            $connection->executeStatement('SET work_mem = \'256MB\'');
            $connection->executeStatement('SET maintenance_work_mem = \'512MB\'');
            self::$bulkTuned = true;
        } catch (\Throwable) {
        }
    }

    /**
     * @return list<string> table names that were set to UNLOGGED
     */
    private static function setUnloggedTables(Connection $connection, EntityManagerInterface $om, string $class): array
    {
        try {
            $platform = $connection->getDatabasePlatform();

            if (!$platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        /** @var \Doctrine\ORM\Mapping\ClassMetadata<object> $metadata */
        $metadata = $om->getClassMetadata($class);
        $tables = [$metadata->getTableName()];

        foreach ($metadata->associationMappings as $assoc) {
            if ($assoc->isOwningSide()) {
                try {
                    $targetMeta = $om->getClassMetadata($assoc->targetEntity);
                    $tables[] = $targetMeta->getTableName();
                } catch (\Throwable) {
                }
            }
        }

        $tables = \array_unique($tables);
        $changed = [];

        foreach ($tables as $table) {
            try {
                $relpersistence = $connection->fetchOne(
                    "SELECT relpersistence FROM pg_class WHERE relname = " . $connection->quote($table),
                );

                if ('p' === $relpersistence) {
                    $connection->executeStatement('ALTER TABLE "' . \str_replace('"', '""', $table) . '" SET UNLOGGED');
                    $changed[] = $table;
                }
            } catch (\Throwable) {
            }
        }

        return $changed;
    }

    /**
     * @param list<string> $tables
     */
    private static function restoreLoggedTables(Connection $connection, array $tables): void
    {
        foreach ($tables as $table) {
            try {
                $connection->executeStatement('ALTER TABLE "' . \str_replace('"', '""', $table) . '" SET LOGGED');
            } catch (\Throwable) {
            }
        }
    }
}
