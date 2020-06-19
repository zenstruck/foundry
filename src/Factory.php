<?php

namespace Zenstruck\Foundry;

use Faker;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class Factory
{
    private static ?Manager $manager = null;

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
     *
     * @return Proxy|object
     */
    final public function create($attributes = []): Proxy
    {
        $proxy = new Proxy($this->instantiate($attributes), self::manager());

        if (!$this->persist) {
            return $proxy;
        }

        $proxy->save()->withoutAutoRefresh();

        foreach ($this->afterPersist as $callback) {
            $this->callAfterPersist($callback, $proxy, $attributes);
        }

        return $proxy->withAutoRefresh();
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

    final public static function boot(Manager $manager): void
    {
        self::$manager = $manager;
    }

    /**
     * @internal
     */
    final public static function manager(): Manager
    {
        if (!self::$manager) {
            throw new \RuntimeException('Factory not yet booted.'); // todo
        }

        return self::$manager;
    }

    final public static function faker(): Faker\Generator
    {
        return self::manager()->faker();
    }

    private function callAfterPersist(callable $callback, Proxy $proxy, array $attributes): void
    {
        $object = $proxy;
        $parameters = (new \ReflectionFunction($callback))->getParameters();

        if (isset($parameters[0]) && $parameters[0]->getType() && $this->class === $parameters[0]->getType()->getName()) {
            $object = $object->object();
        }

        $callback($object, $attributes);
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
        $attributes = \array_map(fn($value) => $this->normalizeAttribute($value), $attributes);

        // instantiate the object with the users instantiator or if not set, the default instantiator
        $object = ($this->instantiator ?? self::manager()->instantiator())($attributes, $this->class);

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
            return \array_map(fn($value) => $this->normalizeAttribute($value), $value);
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
