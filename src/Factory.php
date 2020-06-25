<?php

namespace Zenstruck\Foundry;

use Faker;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Factory
{
    /** @var Configuration|null */
    private static $configuration;

    /** @var string */
    private $class;

    /** @var callable|null */
    private $instantiator;

    /** @var bool */
    private $persist = true;

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
     */
    public function __construct(string $class, $defaultAttributes = [])
    {
        $this->class = $class;
        $this->attributeSet[] = $defaultAttributes;
    }

    /**
     * @param array|callable $attributes
     *
     * @return Proxy|object
     */
    final public function create($attributes = []): Proxy
    {
        $proxy = new Proxy($this->instantiate($attributes));

        if (!$this->persist) {
            return $proxy;
        }

        $proxy->save()->disableAutoRefresh();

        foreach ($this->afterPersist as $callback) {
            $proxy->executeCallback($callback, $attributes);
        }

        return $proxy->enableAutoRefresh();
    }

    /**
     * @param array|callable $attributes
     *
     * @return Proxy[]|object[]
     */
    final public function createMany(int $number, $attributes = []): array
    {
        return \array_map(
            function() use ($attributes) {
                return $this->create($attributes);
            },
            \array_fill(0, $number, null)
        );
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
    final public static function configuration(): Configuration
    {
        if (!self::$configuration) {
            throw new \RuntimeException('Factory not yet booted.'); // todo
        }

        return self::$configuration;
    }

    final public static function faker(): Faker\Generator
    {
        return self::configuration()->faker();
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
    private function instantiate($attributes): object
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

        return $object;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private function normalizeAttribute($value)
    {
        if ($value instanceof Proxy) {
            return $value->object();
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
            return $value;
        }

        if (!$this->persist) {
            // ensure attribute Factory's are also not persisted
            $value = $value->withoutPersisting();
        }

        return $value->create()->object();
    }
}
