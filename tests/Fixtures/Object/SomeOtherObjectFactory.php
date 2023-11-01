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

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<SomeOtherObject>
 *
 * @method        SomeOtherObject|Proxy     create(array|callable $attributes = [])
 * @method static SomeOtherObject|Proxy     createOne(array $attributes = [])
 * @method static SomeOtherObject[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static SomeOtherObject[]|Proxy[] createSequence(iterable|callable $sequence)
 */
final class SomeOtherObjectFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
        ];
    }

    protected static function getClass(): string
    {
        return SomeOtherObject::class;
    }
}
