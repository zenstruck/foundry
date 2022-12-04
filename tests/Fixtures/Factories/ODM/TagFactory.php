<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories\ODM;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMTag;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TagFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return ODMTag::class;
    }

    protected function getDefaults(): array
    {
        return ['name' => self::faker()->sentence()];
    }
}
