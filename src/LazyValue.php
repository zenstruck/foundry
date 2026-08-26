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
final class LazyValue
{
    /** @var \Closure():mixed */
    private \Closure $factory;
    private mixed $memoizedValue = null;

    /**
     * @param callable():mixed $factory
     */
    private function __construct(callable $factory, private bool $memoize = false, private bool $fromFactory = false)
    {
        $this->factory = $factory(...);
    }

    /**
     * @internal
     */
    public function __invoke(): mixed
    {
        if ($this->memoize && isset($this->memoizedValue)) {
            return $this->memoizedValue;
        }

        $value = ($this->factory)();

        if ($value instanceof self) {
            $value = ($value)();
        }

        if ($this->fromFactory) {
            // the factory is created here so that the object it produces is what gets memoized:
            // memoizing the factory itself would create a new object on each use of the value
            $value = $value->create();
        } elseif ($this->memoize && ($value instanceof Factory || $value instanceof FactoryCollection)) {
            trigger_deprecation('zenstruck/foundry', '2.13', 'Passing a factory to memoize() is deprecated: it memoizes the factory and not the object it creates, so a new object is created on each use. Use Factory::memoize() instead, or create the object in the callback.');
        }

        if (\is_array($value)) {
            $value = self::normalizeArray($value);
        }

        if ($this->memoize) {
            return $this->memoizedValue = $value;
        }

        return $value;
    }

    /**
     * @param callable():mixed $factory
     */
    public static function new(callable $factory): self
    {
        return new self($factory, false);
    }

    /**
     * @param callable():mixed $factory
     */
    public static function memoize(callable $factory): self
    {
        return new self($factory, memoize: true);
    }

    /**
     * @internal
     *
     * @param callable():(Factory<mixed>|FactoryCollection<mixed, Factory<mixed>>) $factory
     */
    public static function memoizeFromFactory(callable $factory): self
    {
        return new self($factory, memoize: true, fromFactory: true);
    }

    /**
     * @param  array<array-key, mixed> $value
     * @return array<array-key, mixed>
     */
    private static function normalizeArray(array $value): array
    {
        \array_walk_recursive($value, static function(mixed &$v): void {
            if ($v instanceof self) {
                $v = $v();
            }
        });

        return $value;
    }
}
