<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Factory;

use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Object1;

/**
 * @extends ObjectFactory<Object1>
 */
final class Object1Factory extends ObjectFactory
{
    public static function class(): string
    {
        return Object1::class;
    }

    protected function defaults(): array
    {
        return [
            'prop1' => self::faker()->sentence(),
            'prop2' => self::faker()->sentence(),
            'prop3' => self::faker()->sentence(),
        ];
    }
}
