<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Object;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentObjectFactory<SomeOtherObject>
 *
 * @method        SomeOtherObject|Proxy create(array|callable $attributes = [])
 * @method static SomeOtherObject|Proxy createOne(array $attributes = [])
 * @method static SomeOtherObject[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static SomeOtherObject[]|Proxy[] createSequence(iterable|callable $sequence)
 */
final class SomeOtherObjectFactory extends PersistentObjectFactory
{
    protected function getDefaults(): array
    {
        return [
        ];
    }

    public static function class(): string
    {
        return SomeOtherObject::class;
    }
}
