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

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ODMClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Zenstruck\Foundry\BaseFactory;
use Zenstruck\Foundry\Exception\FoundryBootException;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Object\ObjectFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryAssertions;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of object
 * @extends ObjectFactory<T>
 *
 * @phpstan-type Criteria Proxy<T>|array|mixed
 *
 * @method static list<Proxy&T> createSequence(SequenceAttributes $sequence)
 * @method static list<Proxy&T> createMany(int $number, Attributes $attributes = [])
 *
 * @phpstan-method static list<Proxy<T>> createSequence(SequenceAttributes $sequence)
 * @phpstan-method static list<Proxy<T>> createMany(int $number, Attributes $attributes = [])
 *
 * @phpstan-import-type Parameters from BaseFactory
 * @phpstan-import-type Attributes  from BaseFactory
 * @phpstan-import-type SequenceAttributes from BaseFactory
 */
abstract class PersistentObjectFactory extends ObjectFactory
{
    private static PersistenceManager|null $persistenceManager = null;

    /** @var array<callable(T&Proxy,Parameters):void> */
    private array $afterPersist = [];

    private bool $persist = true;
    private bool $cascadePersist = false;

    /**
     * @internal
     */
    final public static function bootPersistentObjectFactory(PersistenceManager $persistenceManager): void
    {
        self::$persistenceManager = $persistenceManager;
    }

    /**
     * @internal
     */
    final public static function isPersistentObjectFactoryBooted(): bool
    {
        return null !== self::$persistenceManager;
    }

    /**
     * @internal
     */
    final public static function shutdownPersistentObjectFactory(): void
    {
        if (!self::isPersistentObjectFactoryBooted()) {
            return;
        }

        self::$persistenceManager = null;
    }

    /**
     * @internal
     * @throws FoundryBootException
     */
    final public static function persistenceManager(): PersistenceManager
    {
        if (!self::isPersistentObjectFactoryBooted()) {
            throw FoundryBootException::notBootedYet(self::class);
        }

        return self::$persistenceManager; // @phpstan-ignore-line
    }

    /**
     * @see RepositoryProxy::find()
     *
     * @phpstan-param Criteria $criteria
     *
     * @return T&Proxy
     * @phpstan-return Proxy<T>
     *
     * @throws \RuntimeException If no entity found
     */
    final public static function find($criteria): Proxy
    {
        if (null === $proxy = static::repository()->find($criteria)) {
            throw new \RuntimeException(\sprintf('Could not find "%s" object.', static::class()));
        }

        return $proxy;
    }

    final public static function assert(): RepositoryAssertions
    {
        return static::repository()->assert();
    }

    /**
     * @param Parameters $attributes
     *
     * @return T&Proxy
     * @phpstan-return Proxy<T>
     */
    final public static function findOrCreate(array $attributes): object
    {
        try {
            if ($found = static::repository()->find($attributes)) {
                return $found;
            }
        } catch (FoundryBootException) {
        }

        return static::new()->create($attributes);
    }

    /**
     * @return T&Proxy
     * @phpstan-return Proxy<T>
     */
    final public static function first(string $sortedField = 'id'): object
    {
        if (null === $proxy = static::repository()->first($sortedField)) {
            throw new \RuntimeException(\sprintf('No "%s" objects persisted.', static::class()));
        }

        return $proxy;
    }

    /**
     * @return T&Proxy
     * @phpstan-return Proxy<T>
     */
    final public static function last(string $sortedField = 'id'): object
    {
        if (null === $proxy = static::repository()->last($sortedField)) {
            throw new \RuntimeException(\sprintf('No "%s" objects persisted.', static::class()));
        }

        return $proxy;
    }

    /**
     * @return list<T&Proxy>
     * @phpstan-return list<Proxy<T>>
     */
    final public static function all(): array
    {
        return static::repository()->findAll();
    }

    /**
     * @param Parameters $attributes
     *
     * @return list<T&Proxy>
     * @phpstan-return list<Proxy<T>>
     */
    final public static function findBy(array $attributes): array
    {
        return static::repository()->findBy($attributes);
    }

    /**
     * @param Parameters $attributes
     *
     * @return T&Proxy
     * @phpstan-return Proxy<T>
     */
    final public static function random(array $attributes = []): object
    {
        return static::repository()->random($attributes);
    }

    /**
     * @param Parameters $attributes
     *
     * @return T&Proxy
     * @phpstan-return Proxy<T>
     */
    final public static function randomOrCreate(array $attributes = []): object
    {
        try {
            return static::repository()->random($attributes);
        } catch (\RuntimeException) {
            return static::new()->create($attributes);
        }
    }

    /**
     * @param Parameters $attributes
     *
     * @return list<T&Proxy>
     * @phpstan-return list<Proxy<T>>
     */
    final public static function randomSet(int $number, array $attributes = []): array
    {
        return static::repository()->randomSet($number, $attributes);
    }

    /**
     * @param Parameters $attributes
     *
     * @return list<T&Proxy>
     * @phpstan-return list<Proxy<T>>
     */
    final public static function randomRange(int $min, int $max, array $attributes = []): array
    {
        return static::repository()->randomRange($min, $max, $attributes);
    }

    /**
     * @param Parameters $attributes
     */
    final public static function count(array $attributes = []): int
    {
        return static::repository()->count($attributes);
    }

    final public static function truncate(): void
    {
        static::repository()->truncate();
    }

    /**
     * @phpstan-return RepositoryProxy<T>
     */
    final public static function repository(): RepositoryProxy
    {
        return static::persistenceManager()->repositoryFor(static::class());
    }

    /**
     * @return T&Proxy
     * @phpstan-return Proxy<T>
     */
    final public function create(array|callable $attributes = []): object
    {
        [$object, $parameters, $postPersistCallbacks] = $this->normalizeAndInstantiate($attributes);

        $object = new Proxy($object);

        if (!$this->isPersisting()) {
            return $object;
        }

        if ($this->cascadePersist && !$postPersistCallbacks) {
            return $object;
        }

        return $object
            ->save()
            ->withoutAutoRefresh(function(Proxy $proxy) use ($parameters, $postPersistCallbacks): void {
                $callbacks = [...$postPersistCallbacks, ...$this->afterPersist];

                if (!$callbacks) {
                    return;
                }

                foreach ($callbacks as $callback) {
                    $proxy->executeCallback($callback, $parameters);
                }

                $proxy->save(); // save again as afterPersist events may have modified
            })
        ;
    }

    final public function withoutPersisting(): static
    {
        $clone = clone $this;
        $clone->persist = false;

        return $clone;
    }

    /**
     * @param callable(T&Proxy,Parameters):void $callback
     */
    final public function afterPersist(callable $callback): static
    {
        $clone = clone $this;
        $clone->afterPersist[] = $callback;

        return $clone;
    }

    final public static function delayFlush(callable $callback): mixed
    {
        return self::persistenceManager()->delayFlush($callback);
    }

    /**
     * @internal
     */
    final protected function normalizeAttribute(mixed $value, string $name): mixed
    {
        if ($value instanceof Proxy) {
            return $value->isPersisted() ? $value->refresh()->object() : $value->object();
        }

        if (\is_callable($value)) {
            $value = $value();
        }

        if ($value instanceof FactoryCollection) {
            $value = $value->all();
        }

        if (\is_array($value)) {
            return \array_map(
                fn($value) => $this->normalizeAttribute($value, $name),
                $value
            );
        }

        if (!$value instanceof BaseFactory) {
            return \is_object($value) ? self::normalizeObject($value) : $value;
        }

        if (!$value instanceof self) {
            return $value->create();
        }

        if (!$this->isPersisting()) {
            // ensure attribute Factories' are also not persisted
            $value = $value->withoutPersisting();
        }

        if (!self::persistenceManager()->hasManagerRegistry()) {
            return $value->create()->object();
        }

        try {
            $objectManager = self::persistenceManager()->objectManagerFor(static::class());

            if (!$objectManager instanceof EntityManagerInterface || $objectManager->getClassMetadata($value::class())->isEmbeddedClass) {
                // we may deal with ODM document or ORM\Embedded
                return $value->create()->object();
            }
        } catch (\Throwable $e) {
            // not persisted object
            return $value->create();
        }

        $relationshipMetadata = self::getRelationshipMetadata($objectManager, static::class(), $name);

        if (null === $relationshipMetadata) {
            return $value->create()->object();
        }

        if ($relationshipMetadata['isOwningSide']) {
            $cascadePersist = $relationshipMetadata['cascade'];
        } else {
            $isCollection = $relationshipMetadata['inverseIsCollection'];
            $relationshipField = $relationshipMetadata['inversedField'];
            $cascadePersist = $relationshipMetadata['cascade'];

            if ($this->isPersisting() && null !== $relationshipField && false === $cascadePersist) {
                return new InversedRelationshipPostPersistCallback($value, $relationshipField, $isCollection);
            }
        }

        if ($cascadePersist) {
            $value = $value->withCascadePersist();
        }

        return $value->create()->object();
    }

    /**
     * @internal
     * @param Attributes $attributes
     *
     * @return array{0:T, 1:Parameters, 2:list<PostPersistCallback>}
     */
    final protected function normalizeAndInstantiate(array|callable $attributes = []): array
    {
        $parameters = [];
        $callbacks = [];
        foreach ($this->mergedAttributes($attributes) as $name => $attribute) {
            $normalizedAttribute = $this->normalizeAttribute($attribute, $name);

            if (\is_array($normalizedAttribute)) {
                $callbacks = [
                    ...$callbacks,
                    ...\array_values(\array_filter($normalizedAttribute, static fn(mixed $attribute): bool => $attribute instanceof PostPersistCallback)),
                ];

                $normalizedAttribute = \array_filter($normalizedAttribute, static fn(mixed $attribute): bool => !$attribute instanceof PostPersistCallback);
            }

            if ($normalizedAttribute instanceof PostPersistCallback) {
                $callbacks[] = $normalizedAttribute;
                $normalizedAttribute = $attribute instanceof FactoryCollection ? [] : null;
            }

            $parameters[$name] = $normalizedAttribute;
        }

        [$object, $parameters] = $this->instantiate($parameters);

        return [$object, $parameters, $callbacks];
    }

    private static function normalizeObject(object $object): object
    {
        try {
            return Proxy::createFromPersisted($object)->refresh()->object();
        } catch (\RuntimeException) {
            return $object;
        }
    }

    private function isPersisting(): bool
    {
        if (!$this->persist || !self::persistenceManager()->hasManagerRegistry()) {
            return false;
        }

        try {
            $classMetadata = self::persistenceManager()->objectManagerFor(static::class())->getClassMetadata(static::class());
        } catch (\RuntimeException) {
            // entity not managed (perhaps Embeddable)
            return false;
        }

        if ($classMetadata instanceof ORMClassMetadata && $classMetadata->isEmbeddedClass) {
            // embedded entity
            return false;
        }

        if ($classMetadata instanceof ODMClassMetadata && $classMetadata->isEmbeddedDocument) {
            // embedded document
            return false;
        }

        return true;
    }

    /**
     * @param class-string $class
     *
     * @return array{cascade: bool, inversedField: ?string, inverseIsCollection: bool, isOwningSide: bool}|null
     */
    private static function getRelationshipMetadata(EntityManagerInterface $entityManager, string $class, string $relationshipName): ?array
    {
        $metadata = $entityManager->getClassMetadata($class);

        $relationshipMetadata = $metadata->associationMappings[$relationshipName] ?? null;

        if (!$relationshipMetadata) {
            return null;
        }

        return [
            'cascade' => $relationshipMetadata['isCascadePersist'],
            'inversedField' => $relationshipMetadata['inversedBy'] ?? $relationshipMetadata['mappedBy'] ?? null,
            'inverseIsCollection' => $entityManager->getClassMetadata($relationshipMetadata['targetEntity'])->isCollectionValuedAssociation($relationshipMetadata['inversedBy'] ?? $relationshipMetadata['mappedBy']),
            'isOwningSide' => $relationshipMetadata['isOwningSide'],
        ];
    }

    private function withCascadePersist(): static
    {
        $cloned = clone $this;
        $cloned->cascadePersist = true;

        return $cloned;
    }
}
