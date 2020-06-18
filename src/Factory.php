<?php

namespace Zenstruck\Foundry;

use Faker;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Factory
{
    /** @var callable|null */
    private static $defaultInstantiator;
    private static ?Faker\Generator $faker = null;

    private string $class;

    /** @var callable|null */
    private $instantiator;

    private bool $persist = true;

    /** @var array<array|callable> */
    private array $attributeSet = [];

    /** @var callable[] */
    private array $beforeInstantiate = [];

    /** @var callable[] */
    private array $afterInstantiate = [];

    /** @var callable[] */
    private array $afterPersist = [];

    /**
     * @param array|callable $defaultAttributes
     */
    public function __construct(string $class, $defaultAttributes = [])
    {
        $this->class = $class;
        $this->attributeSet[] = $defaultAttributes;
    }

    /**
     * @param array|callable $attributes
     */
    final public function instantiate($attributes = []): object
    {
        return $this->doInstantiate($attributes);
    }

    /**
     * @param array|callable $attributes
     *
     * @return object[]
     */
    final public function instantiateMany(int $number, $attributes = []): array
    {
        return \array_map(fn() => $this->instantiate($attributes), \array_fill(0, $number, null));
    }

    /**
     * @param array|callable $attributes
     *
     * @return Proxy|object
     */
    final public function create($attributes = []): Proxy
    {
        $object = $this->doInstantiate($attributes);

        PersistenceManager::persist($object);

        foreach ($this->afterPersist as $callback) {
            $callback($object, $attributes, PersistenceManager::objectManagerFor($object));
        }

        return Proxy::persisted($object);
    }

    /**
     * @param array|callable $attributes
     *
     * @return Proxy[]|object[]
     */
    final public function createMany(int $number, $attributes = []): array
    {
        return \array_map(fn() => $this->create($attributes), \array_fill(0, $number, null));
    }

    public function withoutPersisting(): self
    {
        $cloned = clone $this;
        $cloned->persist = false;

        return $cloned;
    }

    /**
     * @param array|callable $attributes
     */
    final public function withAttributes($attributes = []): self
    {
        $cloned = clone $this;
        $cloned->attributeSet[] = $attributes;

        return $cloned;
    }

    /**
     * @param callable $callback (array $attributes): array
     */
    final public function beforeInstantiate(callable $callback): self
    {
        $cloned = clone $this;
        $cloned->beforeInstantiate[] = $callback;

        return $cloned;
    }

    /**
     * @param callable $callback (object $object, array $attributes): void
     */
    final public function afterInstantiate(callable $callback): self
    {
        $cloned = clone $this;
        $cloned->afterInstantiate[] = $callback;

        return $cloned;
    }

    /**
     * @param callable $callback (object $object, array $attributes, ObjectManager $objectManager): void
     */
    final public function afterPersist(callable $callback): self
    {
        $cloned = clone $this;
        $cloned->afterPersist[] = $callback;

        return $cloned;
    }

    /**
     * @param callable $instantiator (array $attributes, string $class): object
     */
    final public function instantiator(callable $instantiator): self
    {
        $cloned = clone $this;
        $cloned->instantiator = $instantiator;

        return $cloned;
    }

    /**
     * @param callable $instantiator (array $attributes, string $class): object
     */
    final public static function registerDefaultInstantiator(callable $instantiator): void
    {
        self::$defaultInstantiator = $instantiator;
    }

    final public static function registerFaker(Faker\Generator $faker): void
    {
        self::$faker = $faker;
    }

    final public static function faker(): Faker\Generator
    {
        return self::$faker ?: self::$faker = Faker\Factory::create();
    }

    /**
     * @param array|callable $attributes
     */
    private static function normalizeAttributes($attributes): array
    {
        return \is_callable($attributes) ? $attributes(self::faker()) : $attributes;
    }

    /**
     * @param array|callable $attributes
     */
    private function doInstantiate($attributes): object
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
        $attributes = \array_map(fn($value) => $this->filterNormalizedProperty($value), $attributes);

        // instantiate the object with the users instantiator or if not set, the default instantiator
        $object = ($this->instantiator ?? self::defaultInstantiator())($attributes, $this->class);

        foreach ($this->afterInstantiate as $callback) {
            $callback($object, $attributes);
        }

        return $object;
    }

    private static function defaultInstantiator(): callable
    {
        return self::$defaultInstantiator ?: self::$defaultInstantiator = new Instantiator();
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function filterNormalizedProperty($value)
    {
        if ($value instanceof Proxy) {
            return $value->object();
        }

        if (\is_array($value)) {
            // possible OneToMany/ManyToMany relationship
            return \array_map(fn($value) => $this->filterNormalizedProperty($value), $value);
        }

        if (!$value instanceof self) {
            return $value;
        }

        $value = $value->instantiate();

        return $this->persist ? PersistenceManager::persist($value) : $value;
    }
}
