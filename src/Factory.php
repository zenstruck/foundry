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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Faker;
use Zenstruck\Foundry\Exception\FoundryBootException;
use Zenstruck\Foundry\Persistence\InversedRelationshipPostPersistCallback;
use Zenstruck\Foundry\Persistence\PostPersistCallback;

/**
 * @template TObject of object
 * @abstract
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Factory
{
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
            trigger_deprecation('zenstruck/foundry', '1.9', 'Instantiating "%s" directly is deprecated and this class will be abstract in 2.0, use "anonymous()" function instead.', self::class);
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

        // execute "lazy" values
        $attributes = LazyValue::normalizeArray($attributes);

        foreach ($this->beforeInstantiate as $callback) {
            $attributes = $callback($attributes);

            if (!\is_array($attributes)) {
                throw new \LogicException('Before Instantiate event callback must return an array.');
            }
        }

        // filter each attribute to convert proxies and factories to objects
        $mappedAttributes = [];
        $postPersistCallbacks = [];
        foreach ($attributes as $name => $value) {
            $normalizedAttribute = $this->normalizeAttribute($value, $name);

            if (\is_array($normalizedAttribute)) {
                $postPersistCallbacks = [
                    ...$postPersistCallbacks,
                    ...\array_values(\array_filter($normalizedAttribute, static fn(mixed $attribute): bool => $attribute instanceof PostPersistCallback)),
                ];

                $normalizedAttribute = \array_filter($normalizedAttribute, static fn(mixed $attribute): bool => !$attribute instanceof PostPersistCallback);
            }

            if ($normalizedAttribute instanceof PostPersistCallback) {
                $postPersistCallbacks[] = $normalizedAttribute;
                $normalizedAttribute = $value instanceof FactoryCollection ? [] : null;
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

        if (!$this->isPersisting()) {
            return $proxy;
        }

        if ($this->cascadePersist && !$postPersistCallbacks) {
            return $proxy;
        }

        return $proxy
            ->save()
            ->withoutAutoRefresh(function(Proxy $proxy) use ($attributes, $postPersistCallbacks): void {
                $callbacks = [...$postPersistCallbacks, ...$this->afterPersist];

                if (!$callbacks) {
                    return;
                }

                foreach ($callbacks as $callback) {
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
     * @throws FoundryBootException
     * @internal
     */
    final public static function configuration(): Configuration
    {
        if (!self::isBooted()) {
            throw FoundryBootException::notBootedYet();
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
        } catch (FoundryBootException) {
            throw new \RuntimeException("Cannot get Foundry's configuration. If using faker in a data provider, consider passing attributes as a callable.");
        }
    }

    final public static function delayFlush(callable $callback): mixed
    {
        return self::configuration()->delayFlush($callback);
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

    protected function isPersisting(): bool
    {
        if (!$this->persist || !self::configuration()->isPersistEnabled() || !self::configuration()->hasManagerRegistry()) {
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

    private function normalizeAttributes(array|callable $attributes): array
    {
        return \is_callable($attributes) ? $attributes() : $attributes;
    }

    private function normalizeAttribute(mixed $value, string $name): mixed
    {
        if ($value instanceof Proxy) {
            return $value->isPersisted() ? $value->refresh()->object() : $value->object();
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

        if (!$value instanceof self) {
            return \is_object($value) ? self::normalizeObject($value) : $value;
        }

        if (!$this->isPersisting()) {
            // ensure attribute Factories' are also not persisted
            $value = $value->withoutPersisting();
        }

        if (!self::configuration()->hasManagerRegistry()) {
            return $value->create()->object();
        }

        try {
            $objectManager = self::configuration()->objectManagerFor($this->class);

            if (!$objectManager instanceof EntityManagerInterface || $objectManager->getClassMetadata($value->class)->isEmbeddedClass) {
                // we may deal with ODM document or ORM\Embedded
                return $value->create()->object();
            }
        } catch (\Throwable) {
            // not persisted object
            return $value->create()->object();
        }

        $relationshipMetadata = self::getRelationshipMetadata($objectManager, $this->class, $name);

        if (!$relationshipMetadata) {
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

    private static function normalizeObject(object $object): object
    {
        try {
            return Proxy::createFromPersisted($object)->refresh()->object();
        } catch (\RuntimeException) {
            return $object;
        }
    }

    /**
     * @param class-string $factoryClass
     *
     * @return array{cascade: bool, inversedField: ?string, inverseIsCollection: bool, isOwningSide: bool}|null
     */
    private static function getRelationshipMetadata(EntityManagerInterface $entityManager, string $factoryClass, string $relationshipName): array|null
    {
        $metadata = $entityManager->getClassMetadata($factoryClass);

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
