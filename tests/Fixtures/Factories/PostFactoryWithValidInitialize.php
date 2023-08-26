<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PostFactoryWithValidInitialize extends PostFactory
{
    protected function initialize(): static
    {
        return $this->published();
    }
}
