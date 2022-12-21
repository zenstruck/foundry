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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method static Proxy get(string $name)
 */
abstract class Story
{
    /** @var array<string, Proxy> */
    private array $objects = [];

    /** @var array<string, Proxy[]> */
    private array $pools = [];

    final public function __call(string $method, array $arguments): Proxy
    {
        if ('get' !== $method) {
            return $this->getState($method);
        }

        trigger_deprecation('zenstruck/foundry', '1.24', 'Calling instance method "%1$s::get()" is deprecated and will be removed in 2.0, use the static "%1$s::get()" method instead.', static::class);

        return $this->getState($arguments[0]);
    }

    final public static function __callStatic(string $name, array $arguments): Proxy
    {
        if ('get' !== $name) {
            return static::load()->getState($name);
        }

        return static::load()->getState($arguments[0]);
    }

    final public static function load(): static
    {
        /** @phpstan-ignore-next-line */
        return Factory::configuration()->stories()->load(static::class);
    }

    /**
     * Get all the items in a pool.
     *
     * @return Proxy[]
     */
    final public static function getPool(string $pool): array
    {
        return static::load()->pools[$pool] ?? [];
    }

    /**
     * Get a random item from a pool.
     */
    final public static function getRandom(string $pool): Proxy
    {
        return static::getRandomSet($pool, 1)[0];
    }

    /**
     * Get a random set of items from a pool.
     *
     * @return Proxy[]
     */
    final public static function getRandomSet(string $pool, int $number): array
    {
        if ($number < 1) {
            throw new \InvalidArgumentException(\sprintf('$number must be positive (%d given).', $number));
        }

        return static::getRandomRange($pool, $number, $number);
    }

    /**
     * Get a random range of items from a pool.
     *
     * @return Proxy[]
     */
    final public static function getRandomRange(string $pool, int $min, int $max): array
    {
        if ($min < 0) {
            throw new \InvalidArgumentException(\sprintf('$min must be zero or greater (%d given).', $min));
        }

        if ($max < $min) {
            throw new \InvalidArgumentException(\sprintf('$max (%d) cannot be less than $min (%d).', $max, $min));
        }

        $values = static::getPool($pool);

        \shuffle($values);

        if (\count($values) < $max) {
            throw new \RuntimeException(\sprintf('At least %d items must be in pool "%s" (%d items found).', $max, $pool, \count($values)));
        }

        return \array_slice($values, 0, \random_int($min, $max)); // @phpstan-ignore-line
    }

    /**
     * @param object|Proxy|Factory $object
     */
    final public function add(string $name, object $object): static
    {
        trigger_deprecation('zenstruck\foundry', '1.17.0', 'Using Story::add() is deprecated, use Story::addState().');

        return $this->addState($name, $object);
    }

    abstract public function build(): void;

    /**
     * @param object|Proxy|Factory|object[]|Proxy[]|Factory[]|FactoryCollection $objects
     *
     * @return static
     */
    final protected function addToPool(string $pool, $objects): self
    {
        if ($objects instanceof FactoryCollection) {
            $objects = $objects->create();
        }

        if (!\is_array($objects)) {
            $objects = [$objects];
        }

        foreach ($objects as $object) {
            $this->pools[$pool][] = self::normalizeObject($object);
        }

        return $this;
    }

    /**
     * @param object|Proxy|Factory $object
     *
     * @return static
     */
    final protected function addState(string $name, object $object, ?string $pool = null): self
    {
        $proxy = self::normalizeObject($object);

        $this->objects[$name] = $proxy;

        if ($pool) {
            $this->addToPool($pool, $proxy);
        }

        return $this;
    }

    final protected function getState(string $name): Proxy
    {
        if (!\array_key_exists($name, $this->objects)) {
            throw new \InvalidArgumentException(\sprintf('"%s" was not registered. Did you forget to call "%s::addState()"?', $name, static::class));
        }

        return $this->objects[$name];
    }

    private static function normalizeObject(object $object): Proxy
    {
        // ensure factories are persisted
        if ($object instanceof Factory) {
            $object = $object->create();
        }

        // ensure objects are proxied
        if (!$object instanceof Proxy) {
            $object = new Proxy($object);
        }

        // ensure proxies are persisted
        if (!$object->isPersisted()) {
            $object->save();
        }

        return $object;
    }
}
