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

use Zenstruck\Foundry\AnonymousFactory;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMCategory;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMPost;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMUser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ODMAnonymousFactoryTest extends AnonymousFactoryTest
{
    protected function setUp(): void
    {
        if (!\getenv('USE_ODM')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }

        parent::setUp();
    }

    /**
     * @test
     * @group legacy
     */
    public function can_find_or_create_from_embedded_object()
    {
        $factory = AnonymousFactory::new(ODMPost::class);

        $factory->assert()->count(0);
        $factory->findOrCreate($attributes = ['title' => 'foo', 'body' => 'bar', 'user' => new ODMUser('some user')]);
        $factory->assert()->count(1);
        $factory->findOrCreate($attributes);
        $factory->assert()->count(1);
    }

    protected function categoryClass(): string
    {
        return ODMCategory::class;
    }
}
