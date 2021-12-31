<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\PostFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ODMProxyTest extends ProxyTest
{
    public function setUp(): void
    {
        if (false === \getenv('MONGO_URL')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }
    }

    protected function postFactoryClass(): string
    {
        return PostFactory::class;
    }

    protected function registryServiceId(): string
    {
        return 'doctrine_mongodb';
    }
}
