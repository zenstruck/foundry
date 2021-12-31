<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Document\Category;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\CategoryFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ODMRepositoryProxyTest extends RepositoryProxyTest
{
    protected function setUp(): void
    {
        if (false === \getenv('MONGO_URL')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }
    }

    protected function categoryClass(): string
    {
        return Category::class;
    }

    protected function categoryFactoryClass(): string
    {
        return CategoryFactory::class;
    }
}
