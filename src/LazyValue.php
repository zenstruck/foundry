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
    /** @var callable():mixed */
    private $factory;

    /** @var bool */
    private $memoize;

    /** @var mixed */
    private $memoizedValue = null;

    /**
     * @param callable():mixed $factory
     */
    public function __construct(callable $factory, bool $memoize = false)
    {
        $this->factory = $factory;
        $this->memoize = $memoize;
    }

    public static function memoize(callable $factory): self
    {
        return new self($factory, true);
    }

    /**
     * @internal
     */
    public function __invoke(): mixed
    {
        if ($this->memoize && null !== $this->memoizedValue) {
            return $this->memoizedValue;
        }

        $value = ($this->factory)();

        if ($value instanceof self) {
            $value = ($value)();
        }

        if (\is_array($value)) {
            $value = self::normalizeArray($value);
        }

        if ($this->memoize) {
            $this->memoizedValue = $value;
        }

        return $value;
    }

    /**
     * @internal
     */
    public static function normalizeArray(array $value): array
    {
        \array_walk_recursive($value, static function(mixed &$v): void {
            if ($v instanceof self) {
                $v = $v();
            }
        });

        return $value;
    }
}
