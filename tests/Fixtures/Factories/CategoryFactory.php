<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CategoryFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return Category::class;
    }

    protected function getDefaults(): array
    {
        return ['name' => self::faker()->sentence];
    }
}
