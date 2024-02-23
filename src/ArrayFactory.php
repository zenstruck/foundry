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
 * @phpstan-import-type Parameters from Factory
 * @extends Factory<Parameters>
 */
abstract class ArrayFactory extends Factory
{
    final public function create(callable|array $attributes = []): array
    {
        return $this->normalizeParameters($this->normalizeAttributes($attributes));
    }
}
