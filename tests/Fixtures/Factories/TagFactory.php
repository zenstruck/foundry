<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Tag;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TagFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Tag::class;
    }

    protected function getDefaults(): array
    {
        return ['name' => self::faker()->sentence()];
    }
}
