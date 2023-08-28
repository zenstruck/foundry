<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Object;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<SomeOtherObject>
 *
 *  This factory should extend ObjectFactory because SomeOtherObject class is not "persistable" but let's keep it this way
 *  in order to test legacy behavior.
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
