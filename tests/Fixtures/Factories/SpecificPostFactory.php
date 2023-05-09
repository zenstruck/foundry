<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Tests\Fixtures\Entity\SpecificPost;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class SpecificPostFactory extends PostFactory
{
    public static function class(): string
    {
        return SpecificPost::class;
    }
}
