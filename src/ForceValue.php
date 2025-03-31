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
 * @author NIcolas PHILIPPE <nikophil@gmail.com>
 *
 * @internal
 */
final class ForceValue
{
    public function __construct(public readonly mixed $value)
    {
    }

    /**
     * @param  array<mixed> $what
     * @return array<mixed>
     */
    public static function unwrap(mixed $what): mixed
    {
        if (\is_array($what)) {
            return \array_map(
                self::unwrap(...),
                $what
            );
        }

        return $what instanceof self ? $what->value : $what;
    }
}
