<?php

namespace Zenstruck\Foundry;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ODMClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Faker;

/**
 * @template TObject of object
 * @abstract
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Factory
{
    /** @var Configuration|null */
    private static $configuration;

    /**
     * @var string
     * @psalm-var class-string<TObject>
     */
    private $class;

    /** @var callable|null */
    private $instantiator;

    /** @var bool */
    private $persist = true;

    /** @var bool */
    private $cascadePersist = false;

    /** @var array<array|callable> */
    private $attributeSet = [];

    /** @var callable[] */
    private $beforeInstantiate = [];

    /** @var callable[] */
    private $afterInstantiate = [];

    /** @var callable[] */
    private $afterPersist = [];

    /**
     * @param array|callable $defaultAttributes
     *
     * @psalm-param class-string<TObject> $class
     */
    public function __construct(string $class, $defaultAttributes = [])
    {
        if (self::class === static::class) {
            trigger_deprecation('zenstruck/foundry', '1.9', 'Instantiating "%s" directly is deprecated and this class will be abstract in 2.0, use "%s" instead.', self::class, AnonymousFactory::class);
        }

        $this->class = $class;
        $this->attributeSet[] = $defaultAttributes;
    }

    public function __call(string $name, array $arguments)
    {
        if ('createMany' !== $name) {
            throw new \BadMethodCallException(\sprintf('Call to undefined method "%s::%s".', static::class, $name));
        }

        trigger_deprecation('zenstruck/foundry', '1.7', 'Calling instance method "%1$s::createMany()" is deprecated and will be removed in 2.0, use the static "%1$s:createMany()" method instead.', static::class);

        return $this->many($arguments[0])->create($arguments[1] ?? []);
    }

    /**
     * @param array|callable $attributes
     *
     * @return Proxy<TObject>&TObject
     * @psalm-return Proxy<TObject>
     */
    final public function create($attributes = []): Proxy
    {
        // merge the factory attribute set with the passed attributes
        $attributeSet = \array_merge($this->attributeSet, [$attributes]);

        // normalize each attribute set and collapse
        $attributes = \array_merge(...\array_map([$this, 'normalizeAttributes'], $attributeSet));

        foreach ($this->beforeInstantiate as $callback) {
            $attributes = $callback($attributes);

            if (!\is_array($attributes)) {
                throw new \LogicException('Before Instantiate event callback must return an array.');
            }
        }

        // filter each attribute to convert proxies and factories to objects
        $attributes = \array_map(
            function($value) {
                return $this->normalizeAttribute($value);
            },
            $attributes
        );

        // instantiate the object with the users instantiator or if not set, the default instantiator
        $object = ($this->instantiator ?? self::configuration()->instantiator())($attributes, $this->class);

        foreach ($this->afterInstantiate as $callback) {
            $callback($object, $attributes);
        }

        $proxy = new Proxy($object);

        if (!$this->isPersisting() || true === $this->cascadePersist) {
            return $proxy;
        }

        return $proxy
            ->save()
            ->withoutAutoRefresh(function(Proxy $proxy) use ($attributes) {
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
     * @see FactoryCollection::__construct()
     *
     * @return FactoryCollection<TObject>
     */
    final public function many(int $min, ?int $max = null): FactoryCollection
    {
        return new FactoryCollection($this, $min, $max);
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

        self::$configuration->faker()->unique(true); // reset unique
        self::$configuration = null;
    }

    /**
     * @internal
     * @psalm-suppress InvalidNullableReturnType
     * @psalm-suppress NullableReturnStatement
     */
    final public static function configuration(): Configuration
    {
        if (!self::isBooted()) {
            throw new \RuntimeException('Foundry is not yet booted. Using in a test: is your Test case using the Factories trait? Using in a fixture: is ZenstruckFoundryBundle enabled for this environment?');
        }

        return self::$configuration;
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
        return self::configuration()->faker();
    }

    final public static function delayFlush(callable $callback): void
    {
        self::configuration()->delayFlush($callback);
    }

    /**
     * @internal
     *
     * @psalm-return class-string<TObject>
     */
    final protected function class(): string
    {
        return $this->class;
    }

    /**
     * @param array|callable $attributes
     */
    private static function normalizeAttributes($attributes): array
    {
        return \is_callable($attributes) ? $attributes(self::faker()) : $attributes;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function normalizeAttribute($value)
    {
        if ($value instanceof Proxy) {
            return $value->isPersisted() ? $value->refresh()->object() : $value->object();
        }

        if ($value instanceof FactoryCollection) {
            $value = $this->normalizeCollection($value);
        }

        if (\is_array($value)) {
            // possible OneToMany/ManyToMany relationship
            return \array_map(
                function($value) {
                    return $this->normalizeAttribute($value);
                },
                $value
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
            $relationField = $this->relationshipField($value);
            $value->cascadePersist = $this->hasCascadePersist($value, $relationField);
        }

        return $value->create()->object();
    }

    private static function normalizeObject(object $object): object
    {
        try {
            return Proxy::createFromPersisted($object)->refresh()->object();
        } catch (\RuntimeException $e) {
            return $object;
        }
    }

    private function normalizeCollection(FactoryCollection $collection): array
    {
        if ($this->isPersisting()) {
            $field = $this->inverseRelationshipField($collection->factory());
            $cascadePersist = $this->hasCascadePersist($collection->factory(), $field);

            if ($field && false === $cascadePersist) {
                $this->afterPersist[] = static function(Proxy $proxy) use ($collection, $field) {
                    $collection->create([$field => $proxy]);
                    $proxy->refresh();
                };

                // creation delegated to afterPersist event - return empty array here
                return [];
            }
        }

        return \array_map(
            function(self $factory) {
                $factory->cascadePersist = $this->cascadePersist;

                return $factory;
            },
            $collection->all()
        );
    }

    private function relationshipField(self $factory): ?string
    {
        $factoryClass = $this->class;
        $relationClass = $factory->class;

        // Check inversedBy side ($this is the owner of the relation)
        $factoryClassMetadata = self::configuration()->objectManagerFor($factoryClass)->getMetadataFactory()->getMetadataFor($factoryClass);

        if (!$factoryClassMetadata instanceof ORMClassMetadata) {
            return null;
        }

        foreach ($factoryClassMetadata->getAssociationNames() as $field) {
            if (!$factoryClassMetadata->isAssociationInverseSide($field) && $factoryClassMetadata->getAssociationTargetClass($field) === $relationClass) {
                return $field;
            }
        }

        try {
            // Check mappedBy side ($factory is the owner of the relation)
            $relationClassMetadata = self::configuration()->objectManagerFor($relationClass)->getClassMetadata($relationClass);
        } catch (\RuntimeException $e) {
            // relation not managed - could be embeddable
            return null;
        }

        foreach ($relationClassMetadata->getAssociationNames() as $field) {
            if (($relationClassMetadata->isSingleValuedAssociation($field) || $relationClassMetadata->isCollectionValuedAssociation($field)) && $relationClassMetadata->getAssociationTargetClass($field) === $factoryClass) {
                return $field;
            }
        }

        return null; // no relationship found
    }

    private function inverseRelationshipField(self $factory): ?string
    {
        $collectionClass = $factory->class;
        $collectionMetadata = self::configuration()->objectManagerFor($collectionClass)->getClassMetadata($collectionClass);

        foreach ($collectionMetadata->getAssociationNames() as $field) {
            // ensure 1-n and associated class matches
            if ($collectionMetadata->isSingleValuedAssociation($field) && $collectionMetadata->getAssociationTargetClass($field) === $this->class) {
                return $field;
            }
        }

        return null; // no relationship found
    }

    private function hasCascadePersist(self $factory, ?string $field): bool
    {
        if (null === $field) {
            return false;
        }

        $factoryClass = $this->class;
        $relationClass = $factory->class;
        $classMetadataFactory = self::configuration()->objectManagerFor($factoryClass)->getMetadataFactory()->getMetadataFor($factoryClass);
        $relationClassMetadata = self::configuration()->objectManagerFor($relationClass)->getClassMetadata($relationClass);

        if (!$relationClassMetadata instanceof ClassMetadataInfo || !$classMetadataFactory instanceof ClassMetadataInfo) {
            return false;
        }

        if ($relationClassMetadata->hasAssociation($field)) {
            $inversedBy = $relationClassMetadata->getAssociationMapping($field)['inversedBy'];
            if (null === $inversedBy) {
                return false;
            }

            $cascadeMetadata = $classMetadataFactory->getAssociationMapping($inversedBy)['cascade'] ?? [];
        } else {
            $cascadeMetadata = $classMetadataFactory->getAssociationMapping($field)['cascade'] ?? [];
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
        } catch (\RuntimeException $e) {
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
