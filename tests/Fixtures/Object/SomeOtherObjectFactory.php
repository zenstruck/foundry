<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Object;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<SomeOtherObject>
 *
 * @method        SomeOtherObject|Proxy create(array|callable $attributes = [])
 * @method static SomeOtherObject|Proxy createOne(array $attributes = [])
 * @method static SomeOtherObject[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static SomeOtherObject[]|Proxy[] createSequence(iterable|callable $sequence)
 */
final class SomeOtherObjectFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
        ];
    }

    protected static function getClass(): string
    {
        return SomeOtherObject::class;
    }
}
