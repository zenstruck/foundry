<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories\ODM;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMCategory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CategoryFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ODMCategory::class;
    }

    protected function getDefaults(): array
    {
        return ['name' => self::faker()->sentence()];
    }
}
