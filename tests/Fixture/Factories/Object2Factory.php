<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Factories;

use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Object2;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @extends ObjectFactory<Object2>
 */
final class Object2Factory extends ObjectFactory
{
    public static function class(): string
    {
        return Object2::class;
    }

    protected function defaults(): array
    {
        return [
            'object' => Object1Factory::new(),
        ];
    }
}
