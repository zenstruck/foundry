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
 */
abstract class Story
{
    /** @var array<string,mixed> */
    private array $state = [];

    /** @var array<string, mixed[]> */
    private array $pools = [];

    /**
     * @param mixed[] $arguments
     */
    final public function __call(string $method, array $arguments): mixed
    {
        return $this->getState($method);
    }

    /**
     * @param mixed[] $arguments
     */
    final public static function __callStatic(string $name, array $arguments): mixed
    {
        return static::get($name);
    }

    final public static function get(string $state): mixed
    {
        return static::load()->getState($state);
    }

    /**
     * Get all the items in a pool.
     *
     * @return mixed[]
     */
    final public static function getPool(string $pool): array
    {
        return static::load()->pools[$pool] ?? [];
    }

    /**
     * Get a random item from a pool.
     */
    final public static function getRandom(string $pool): mixed
    {
        return static::getRandomSet($pool, 1)[0];
    }

    /**
     * Get a random set of items from a pool.
     *
     * @return mixed[]
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
     * @return mixed[]
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

    final public static function load(): static
    {
        return Configuration::instance()->stories->load(static::class);
    }

    abstract public function build(): void;

    final protected function addState(string $name, mixed $value, ?string $pool = null): static
    {
        $value = self::normalizeFactory($value);

        $this->state[$name] = $value;

        if ($pool) {
            $this->addToPool($pool, $value);
        }

        return $this;
    }

    final protected function getState(string $name): mixed
    {
        if (!\array_key_exists($name, $this->state)) {
            throw new \InvalidArgumentException(\sprintf('"%s" was not registered. Did you forget to call "%s::addState()"?', $name, static::class));
        }

        return $this->state[$name];
    }

    final protected function addToPool(string $pool, mixed $value): self
    {
        if ($value instanceof FactoryCollection) {
            $value = $value->create();
        }

        if (!\is_array($value)) {
            $value = [$value];
        }

        foreach ($value as $item) {
            $this->pools[$pool][] = self::normalizeFactory($item);
        }

        return $this;
    }

    private static function normalizeFactory(mixed $value): mixed
    {
        return $value instanceof Factory ? $value->create() : $value;
    }
}
