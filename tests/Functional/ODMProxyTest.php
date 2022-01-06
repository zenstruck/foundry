<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Document\Post;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\PostFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ODMProxyTest extends ProxyTest
{
    protected function setUp(): void
    {
        if (false === \getenv('MONGO_URL')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }
    }

    protected function postFactoryClass(): string
    {
        return PostFactory::class;
    }

    protected function postClass(): string
    {
        return Post::class;
    }

    protected function registryServiceId(): string
    {
        return 'doctrine_mongodb';
    }
}
