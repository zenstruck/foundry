<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories\ODM;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMCategory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CategoryFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return ODMCategory::class;
    }

    protected function getDefaults(): array
    {
        return ['name' => self::faker()->sentence()];
    }
}
