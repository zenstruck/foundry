<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class SimpleObject
{
    public function __construct(
        public string $prop1,
        public ?string $prop2 = null,
    ) {
    }
}
