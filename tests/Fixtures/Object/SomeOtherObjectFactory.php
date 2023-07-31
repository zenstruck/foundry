<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Object;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<SomeOtherObject>
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
