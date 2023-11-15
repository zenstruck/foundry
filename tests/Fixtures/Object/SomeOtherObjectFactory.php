<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixtures\Object;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 * @extends PersistentObjectFactory<SomeOtherObject>
 *
 * @method        SomeOtherObject|Proxy     create(array|callable $attributes = [])
 * @method static SomeOtherObject|Proxy     createOne(array $attributes = [])
 * @method static SomeOtherObject[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static SomeOtherObject[]|Proxy[] createSequence(iterable|callable $sequence)
 */
final class SomeOtherObjectFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return SomeOtherObject::class;
    }

    protected function defaults(): array|callable
    {
        return [
        ];
    }
}
