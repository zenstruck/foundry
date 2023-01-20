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

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ODMClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Faker;
use Zenstruck\Foundry\Exception\FoundryNotBootedException;

/**
 * @template TObject of object
 * @abstract
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Factory
{
    private const NULL_VALUE = '__null_value';

    private static ?Configuration $configuration = null;

    /**
     * @var class-string<TObject>
     */
    private string $class;

    /** @var callable|null */
    private $instantiator;

    private bool $persist = true;

    private bool $cascadePersist = false;

    /** @var array<array|callable> */
    private array $attributeSet = [];

    /** @var callable[] */
    private array $beforeInstantiate = [];

    /** @var callable[] */
    private array $afterInstantiate = [];

    /** @var callable[] */
    private array $afterPersist = [];

    /**
     * @param class-string<TObject> $class
     */
    public function __construct(string $class, array|callable $defaultAttributes = [])
    {
        /** @phpstan-ignore-next-line */
        if (self::class === static::class) {
            trigger_deprecation('zenstruck/foundry', '1.9', 'Instantiating "%s" directly is deprecated and this class will be abstract in 2.0, use "%s" instead.', self::class, AnonymousFactory::class);
        }

        $this->class = $class;
        $this->attributeSet[] = $defaultAttributes;
    }

    /**
     * @phpstan-return list<Proxy<TObject>>
     */
    public function __call(string $name, array $arguments): array
    {
        if ('createMany' !== $name) {
            throw new \BadMethodCallException(\sprintf('Call to undefined method "%s::%s".', static::class, $name));
        }

        trigger_deprecation('zenstruck/foundry', '1.7', 'Calling instance method "%1$s::createMany()" is deprecated and will be removed in 2.0, use e.g. "%1$s::new()->stateAdapter()->many(%2$d)->create()" instead.', static::class, $arguments[0]);

        return $this->many($arguments[0])->create($arguments[1] ?? []);
    }

    /**
     * @return Proxy<TObject>&TObject
     * @phpstan-return Proxy<TObject>
     */
    final public function create(array|callable $attributes = []): Proxy
    {
        // merge the factory attribute set with the passed attributes
        $attributeSet = \array_merge($this->attributeSet, [$attributes]);

        // normalize each attribute set and collapse
        $attributes = \array_merge(...\array_map(fn(callable|array $attributes): array => $this->normalizeAttributes($attributes), $attributeSet));

        foreach ($this->beforeInstantiate as $callback) {
            $attributes = $callback($attributes);

            if (!\is_array($attributes)) {
                throw new \LogicException('Before Instantiate event callback must return an array.');
            }
        }

        // filter each attribute to convert proxies and factories to objects
        $mappedAttributes = [];
        foreach ($attributes as $name => $value) {
            $normalizedAttribute = $this->normalizeAttribute($value, $name);
            if (self::NULL_VALUE === $normalizedAttribute) {
                $normalizedAttribute = null;
            }

            $mappedAttributes[$name] = $normalizedAttribute;
        }

        $attributes = $mappedAttributes;

        // instantiate the object with the users instantiator or if not set, the default instantiator
        /** @var TObject $object */
        $object = ($this->instantiator ?? self::configuration()->instantiator())($attributes, $this->class);

        foreach ($this->afterInstantiate as $callback) {
            $callback($object, $attributes);
        }

        $proxy = new Proxy($object);

        if (!$this->isPersisting() || $this->cascadePersist) {
            return $proxy;
        }

        return $proxy
            ->save()
            ->withoutAutoRefresh(function(Proxy $proxy) use ($attributes): void {
                if (!$this->afterPersist) {
                    return;
                }

                foreach ($this->afterPersist as $callback) {
                    $proxy->executeCallback($callback, $attributes);
                }

                $proxy->save(); // save again as afterPersist events may have modified
            })
        ;
    }

    /**
     * @param int|null $max If set, when created, the collection will be a random size between $min and $max
     *
     * @return FactoryCollection<TObject>
     */
    final public function many(int $min, ?int $max = null): FactoryCollection
    {
        if (!$max) {
            return FactoryCollection::set($this, $min);
        }

        return FactoryCollection::range($this, $min, $max);
    }

    /**
     * @param iterable<array<string, mixed>>|callable(): iterable<array<string, mixed>> $sequence
     *
     * @return FactoryCollection<TObject>
     */
    final public function sequence(iterable|callable $sequence): FactoryCollection
    {
        if (\is_callable($sequence)) {
            $sequence = $sequence();
        }

        return FactoryCollection::sequence($this, $sequence);
    }

    /**
     * @return static
     */
    public function withoutPersisting(): self
    {
        $cloned = clone $this;
        $cloned->persist = false;

        return $cloned;
    }

    /**
     * @param array|callable $attributes
     *
     * @return static
     */
    final public function withAttributes($attributes = []): self
    {
        $cloned = clone $this;
        $cloned->attributeSet[] = $attributes;

        return $cloned;
    }

    /**
     * @param callable $callback (array $attributes): array
     *
     * @return static
     */
    final public function beforeInstantiate(callable $callback): self
    {
        $cloned = clone $this;
        $cloned->beforeInstantiate[] = $callback;

        return $cloned;
    }

    /**
     * @param callable $callback (object $object, array $attributes): void
     *
     * @return static
     */
    final public function afterInstantiate(callable $callback): self
    {
        $cloned = clone $this;
        $cloned->afterInstantiate[] = $callback;

        return $cloned;
    }

    /**
     * @param callable $callback (object|Proxy $object, array $attributes): void
     *
     * @return static
     */
    final public function afterPersist(callable $callback): self
    {
        $cloned = clone $this;
        $cloned->afterPersist[] = $callback;

        return $cloned;
    }

    /**
     * @param callable $instantiator (array $attributes, string $class): object
     *
     * @return static
     */
    final public function instantiateWith(callable $instantiator): self
    {
        $cloned = clone $this;
        $cloned->instantiator = $instantiator;

        return $cloned;
    }

    /**
     * @internal
     */
    final public static function boot(Configuration $configuration): void
    {
        self::$configuration = $configuration;
    }

    /**
     * @internal
     */
    final public static function shutdown(): void
    {
        if (!self::isBooted()) {
            return;
        }

        self::configuration()->faker()->unique(true); // reset unique
        self::$configuration = null;
    }

    /**
     * @internal
     * @throws FoundryNotBootedException
     */
    final public static function configuration(): Configuration
    {
        if (!self::isBooted()) {
            throw new FoundryNotBootedException();
        }

        return self::$configuration; // @phpstan-ignore-line
    }

    /**
     * @internal
     */
    final public static function isBooted(): bool
    {
        return null !== self::$configuration;
    }

    final public static function faker(): Faker\Generator
    {
        try {
            return self::configuration()->faker();
        } catch (FoundryNotBootedException $exception) {
            throw new \RuntimeException(
                "Cannot get Foundry's configuration. If using faker in a data provider, consider passing attributes as a callable."
            );
        }
    }

    final public static function delayFlush(callable $callback): void
    {
        self::configuration()->delayFlush($callback);
    }

    /**
     * @internal
     *
     * @phpstan-return class-string<TObject>
     */
    final protected function class(): string
    {
        return $this->class;
    }

    private function normalizeAttributes(array|callable $attributes): array
    {
        return \is_callable($attributes) ? $attributes() : $attributes;
    }

    /**
     * @return mixed
     */
    private function normalizeAttribute(mixed $value, ?string $name = null)
    {
        if ($value instanceof Proxy) {
            return $value->isPersisted() ? $value->refresh()->object() : $value->object();
        }

        if ($value instanceof FactoryCollection) {
            $value = $this->normalizeCollection($name, $value);
        }

        if (\is_array($value)) {
            // possible OneToMany/ManyToMany relationship
            return \array_filter(
                \array_map(
                    fn($value) => $this->normalizeAttribute($value, $name),
                    $value
                ),
                static fn($value): bool => self::NULL_VALUE !== $value
            );
        }

        if (!$value instanceof self) {
            return \is_object($value) ? self::normalizeObject($value) : $value;
        }

        if (!$this->isPersisting()) {
            // ensure attribute Factory's are also not persisted
            $value = $value->withoutPersisting();
        }

        // Check if the attribute is cascade persist
        if (self::configuration()->hasManagerRegistry()) {
            $ownedRelationshipField = $this->ownedRelationshipField($name, $value);

            if (null !== $ownedRelationshipField) {
                $cascadePersist = $this->hasCascadePersist(null, $ownedRelationshipField);
            } else {
                $isCollection = false;
                $relationshipField = $this->inverseRelationshipField($name, $value, $isCollection);
                $cascadePersist = $this->hasCascadePersist($value, $relationshipField);

                if ($this->isPersisting() && null !== $relationshipField && false === $cascadePersist) {
                    $this->afterPersist[] = static function(Proxy $proxy) use ($value, $relationshipField, $isCollection): void {
                        $value->create([$relationshipField => $isCollection ? [$proxy] : $proxy]);
                        $proxy->refresh();
                    };

                    // creation delegated to afterPersist event - return null here
                    return self::NULL_VALUE;
                }
            }

            $value->cascadePersist = $cascadePersist;
        }

        return $value->create()->object();
    }

    private static function normalizeObject(object $object): object
    {
        try {
            return Proxy::createFromPersisted($object)->refresh()->object();
        } catch (\RuntimeException) {
            return $object;
        }
    }

    private function normalizeCollection(?string $relationName, FactoryCollection $collection): array
    {
        if ($this->isPersisting()) {
            $field = $this->inverseCollectionRelationshipField($relationName, $collection->factory());
            $cascadePersist = $this->hasCascadePersist($collection->factory(), $field);

            if ($field && false === $cascadePersist) {
                $this->afterPersist[] = static function(Proxy $proxy) use ($collection, $field): void {
                    $collection->create([$field => $proxy]);
                    $proxy->refresh();
                };

                // creation delegated to afterPersist event - return empty array here
                return [];
            }
        }

        return \array_map(
            function(self $factory): self {
                $factory->cascadePersist = $this->cascadePersist;

                return $factory;
            },
            $collection->all()
        );
    }

    private function ownedRelationshipField(?string $relationName, self $factory): ?string
    {
        $factoryClass = $this->class;
        $relationClass = $factory->class;

        // Check inversedBy side ($this is the owner of the relation)
        $factoryClassMetadata = self::configuration()->objectManagerFor($factoryClass)->getMetadataFactory()->getMetadataFor($factoryClass);

        if (!$factoryClassMetadata instanceof ORMClassMetadata) {
            return null;
        }

        if (null !== $relationName && $factoryClassMetadata->hasAssociation($relationName)) {
            if (\is_a($factory->class, $factoryClassMetadata->getAssociationTargetclass($relationName), true) && !$factoryClassMetadata->isAssociationInverseSide($relationName)) {
                return $relationName;
            }
        } else {
            $relationName = null;
        }

        foreach ($factoryClassMetadata->getAssociationNames() as $field) {
            if (
                !$factoryClassMetadata->isAssociationInverseSide($field)
                && \is_a($relationClass, $factoryClassMetadata->getAssociationTargetClass($field), true)
                && (null === $relationName || ($factoryClassMetadata->getAssociationMapping($field)['inversedBy'] ?? null) === $relationName)
            ) {
                return $field;
            }
        }

        return null; // no relationship found
    }

    private function inverseRelationshipField(?string $relationName, self $factory, ?bool &$isCollectionValuedRelation): ?string
    {
        $factoryClass = $this->class;
        $relationClass = $factory->class;

        // Check inversedBy side ($this is the owner of the relation)
        $factoryClassMetadata = self::configuration()->objectManagerFor($factoryClass)->getMetadataFactory()->getMetadataFor($factoryClass);

        if (!$factoryClassMetadata instanceof ORMClassMetadata) {
            return null;
        }

        if ($factoryClassMetadata->hasAssociation($relationName)) {
            $relationName = $factoryClassMetadata->getAssociationMappedByTargetField($relationName);
        } else {
            $relationName = null;
        }

        try {
            // Check mappedBy side ($factory is the owner of the relation)
            $relationClassMetadata = self::configuration()->objectManagerFor($relationClass)->getClassMetadata($relationClass);
        } catch (\RuntimeException) {
            // relation not managed - could be embeddable
            return null;
        }

        $isCollectionValuedRelation = false;
        foreach ($relationClassMetadata->getAssociationNames() as $field) {
            if (
                ($relationClassMetadata->isSingleValuedAssociation($field) || $relationClassMetadata->isCollectionValuedAssociation($field))
                && ($relationClassMetadata->getAssociationTargetClass($field) && \is_a($factoryClass, $relationClassMetadata->getAssociationTargetClass($field), true))
                && (null === $relationName || !$relationClassMetadata instanceof ORMClassMetadata || $field === $relationName)
            ) {
                $isCollectionValuedRelation = $relationClassMetadata->isCollectionValuedAssociation($field);

                return $field;
            }
        }

        return null; // no relationship found
    }

    private function inverseCollectionRelationshipField(?string $relationName, self $factory): ?string
    {
        $factoryClass = $this->class;
        $collectionClass = $factory->class;
        $factoryClassMetadata = self::configuration()->objectManagerFor($factoryClass)->getMetadataFactory()->getMetadataFor($factoryClass);
        $collectionMetadata = self::configuration()->objectManagerFor($collectionClass)->getClassMetadata($collectionClass);

        if (null !== $relationName && $factoryClassMetadata instanceof ORMClassMetadata && $factoryClassMetadata->hasAssociation($relationName)) {
            $mappedBy = $factoryClassMetadata->getAssociationMappedByTargetField($relationName);
            if (
                \is_a($factory->class, $factoryClassMetadata->getAssociationTargetclass($relationName), true)
                && null !== $mappedBy
                && $collectionMetadata->isSingleValuedAssociation($mappedBy)
            ) {
                return $mappedBy;
            }
        } else {
            $relationName = null;
        }

        foreach ($collectionMetadata->getAssociationNames() as $field) {
            // ensure 1-n and associated class matches
            if (
                $collectionMetadata->isSingleValuedAssociation($field)
                && ($collectionMetadata->getAssociationTargetClass($field) && \is_a($this->class, $collectionMetadata->getAssociationTargetClass($field), true))
                && (!$collectionMetadata instanceof ORMClassMetadata || null === $relationName || ($collectionMetadata->getAssociationMapping($field)['inversedBy'] ?? null) === $relationName)
            ) {
                return $field;
            }
        }

        return null; // no relationship found
    }

    private function hasCascadePersist(?self $factory, ?string $field): bool
    {
        if (null === $field) {
            return false;
        }

        $factoryClass = $this->class;
        $relationClass = $factory->class ?? null;
        $classMetadataFactory = self::configuration()->objectManagerFor($factoryClass)->getMetadataFactory()->getMetadataFor($factoryClass);
        $relationClassMetadata = null !== $relationClass ? self::configuration()->objectManagerFor($relationClass)->getClassMetadata($relationClass) : null;

        if (!$classMetadataFactory instanceof ClassMetadataInfo) {
            return false;
        }

        if ($relationClassMetadata instanceof ClassMetadataInfo && $relationClassMetadata->hasAssociation($field)) {
            $inversedBy = $relationClassMetadata->getAssociationMapping($field)['inversedBy'];
            if (null === $inversedBy) {
                return false;
            }

            $cascadeMetadata = $classMetadataFactory->getAssociationMapping($inversedBy)['cascade'];
        } else {
            $cascadeMetadata = $classMetadataFactory->getAssociationMapping($field)['cascade'];
        }

        return \in_array('persist', $cascadeMetadata, true);
    }

    private function isPersisting(): bool
    {
        if (!$this->persist || !self::configuration()->hasManagerRegistry()) {
            return false;
        }

        try {
            $classMetadata = self::configuration()->objectManagerFor($this->class)->getClassMetadata($this->class);
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
}
