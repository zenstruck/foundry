<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Tests\Fixtures\Entity\SpecificPost;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class SpecificPostFactory extends PostFactory
{
    protected static function getClass(): string
    {
        return SpecificPost::class;
    }
}
