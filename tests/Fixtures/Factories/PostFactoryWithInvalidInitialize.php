<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PostFactoryWithInvalidInitialize extends PostFactory
{
    protected function initialize(): PostFactory|PostFactoryWithInvalidInitialize
    {
        return PostFactory::new();
    }
}
