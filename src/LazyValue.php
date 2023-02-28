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

    /**
     * @param callable():mixed $factory
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @internal
     */
    public function __invoke(): mixed
    {
        $value = ($this->factory)();

        if ($value instanceof self) {
            return ($value)();
        }

        if (\is_array($value)) {
            return self::normalizeArray($value);
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
