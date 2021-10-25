<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\Product;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CascadeProductFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return Product::class;
    }

    protected function getDefaults(): array
    {
        return ['name' => self::faker()->word()];
    }
}
