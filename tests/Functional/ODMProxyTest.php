<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Document\ODMPost;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\PostFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ODMProxyTest extends ProxyTest
{
    protected function setUp(): void
    {
        if (!\getenv('MONGO_URL')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }
    }

    protected function postFactoryClass(): string
    {
        return PostFactory::class;
    }

    protected function postClass(): string
    {
        return ODMPost::class;
    }

    protected function registryServiceId(): string
    {
        return 'doctrine_mongodb';
    }
}
