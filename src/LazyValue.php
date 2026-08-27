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

        // memoizeFromFactory() creates the object in its own callback, so what is memoized here is
        // the object; a user callback returning a factory memoizes the factory instead, which
        // creates a new object on each use of the value
        if (!$this->fromFactory && $this->memoize && ($value instanceof Factory || $value instanceof FactoryCollection)) {
            trigger_deprecation('zenstruck/foundry', '2.13', 'Passing a factory to memoize() is deprecated and will throw an error in Foundry 3: it memoizes the factory and not the object it creates, so a new object is created on each use. Use Factory::new()->memoize() instead, or create the object in the callback.');
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
     * @param Factory<mixed>|FactoryCollection<mixed, Factory<mixed>> $factory
     */
    public static function memoizeFromFactory(Factory|FactoryCollection $factory): self
    {
        return new self(static fn() => $factory->create(), memoize: true, fromFactory: true);
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
