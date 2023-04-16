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
use Zenstruck\Foundry\Tests\Fixtures\Entity\Address;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Contact;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ORMAnonymousFactoryTest extends AnonymousFactoryTest
{
    protected function setUp(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        parent::setUp();
    }

    /**
     * @test
     * @group legacy
     */
    public function can_find_or_create_from_embedded_object()
    {
        $factory = AnonymousFactory::new(Contact::class);

        $factory->assert()->count(0);
        $factory->findOrCreate($attributes = ['name' => 'foo', 'address' => new Address('some address')]);
        $factory->assert()->count(1);
        $factory->findOrCreate($attributes);
        $factory->assert()->count(1);
    }

    protected function categoryClass(): string
    {
        return Category::class;
    }
}
