<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Document\Category;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ODMAnonymousFactoryTest extends AnonymousFactoryTest
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
}
