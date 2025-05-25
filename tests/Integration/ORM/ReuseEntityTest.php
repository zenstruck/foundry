<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\AddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Tag\TagFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

final class ReuseEntityTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    /**
     * @test
     */
    #[Test]
    public function it_can_reuse_an_object_in_one_to_one(): void
    {
        $address = AddressFactory::createOne();

        $contact = ContactFactory::new()
            ->reuse($address)
            ->create();

        self::assertSame($address, $contact->getAddress());
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_reuse_an_object_in_inverse_one_to_one(): void
    {
        $contact = ContactFactory::createOne();

        $address = AddressFactory::new()
            ->reuse($contact)
            ->create();

        self::assertSame($contact, $address->getContact());
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_propagate_reused_objects_through_inversed_one_to_one(): void
    {
        $category = CategoryFactory::createOne();

        $address = AddressFactory::new(['contact' => ContactFactory::new()])
            ->reuse($category)
            ->create();

        self::assertSame($category, $address->getContact()?->getCategory());
        self::assertSame($category, $address->getContact()->getSecondaryCategory());
    }

    /**
     * @test
     */
    #[Test]
    public function reused_object_in_sub_factory_has_priority(): void
    {
        $category = CategoryFactory::createOne();

        $address = AddressFactory::new([
            'contact' => ContactFactory::new()->reuse($category2 = CategoryFactory::createOne()),
        ])
            ->reuse($category)
            ->create();

        self::assertSame($category2, $address->getContact()?->getCategory());
        self::assertSame($category2, $address->getContact()->getSecondaryCategory());
    }

    /**
     * @test
     */
    #[Test]
    public function reuse_has_no_effect_on_collections(): void
    {
        $contact = ContactFactory::createOne();

        $category = CategoryFactory::new()
            ->reuse($contact)
            ->create(['contacts' => ContactFactory::new()->many(2)]);

        self::assertNotSame($contact, $category->getContacts()[0]);
        self::assertNotSame($contact, $category->getContacts()[1]);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_propagate_reused_objects_through_inversed_one_to_many(): void
    {
        $address = AddressFactory::createOne();

        $category = CategoryFactory::new()
            ->reuse($address)
            ->create(['contacts' => ContactFactory::new()->many(2)]);

        self::assertCount(2, $category->getContacts());
        foreach ($category->getContacts() as $contact) {
            self::assertSame($address, $contact->getAddress());
        }
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_propagate_reused_objects_through_inversed_many_to_many(): void
    {
        $address = AddressFactory::createOne();

        $tag = TagFactory::new()
            ->reuse($address)
            ->create(['contacts' => ContactFactory::new()->many(2)]);

        self::assertCount(2, $tag->getContacts());
        foreach ($tag->getContacts() as $contact) {
            self::assertSame($address, $contact->getAddress());
        }
    }
}
