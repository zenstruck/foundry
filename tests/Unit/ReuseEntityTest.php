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

namespace Zenstruck\Foundry\Tests\Unit;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RelationshipOnInterface;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\AddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;

use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\object;
use function Zenstruck\Foundry\Persistence\proxy;

final class ReuseEntityTest extends TestCase
{
    use Factories;

    /**
     * @test
     */
    #[Test]
    public function it_can_reuse_an_object(): void
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
    #[IgnoreDeprecations]
    public function it_can_reuse_a_proxy_object(): void
    {
        $address = AddressFactory::createOne();

        $contact = ContactFactory::new()
            ->reuse(proxy($address))
            ->create();

        self::assertSame($address, $contact->getAddress());
    }

    /**
     * @test
     */
    #[Test]
    public function last_reused_object_is_used_if_recycling_two_objects_of_same_type(): void
    {
        $contact = ContactFactory::new()
            ->reuse(AddressFactory::createOne())
            ->reuse($address = AddressFactory::createOne())
            ->create();

        self::assertSame($address, $contact->getAddress());
    }

    /**
     * @test
     */
    #[Test]
    public function it_throws_if_recycling_two_objects_of_same_type_with_spread_parameters(): void
    {
        $contact = ContactFactory::new()
            ->reuse(AddressFactory::createOne(), $address = AddressFactory::createOne())
            ->create();

        self::assertSame($address, $contact->getAddress());
    }

    /**
     * @test
     */
    #[Test]
    public function it_throws_if_recycling_a_factory(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ContactFactory::new()
            ->reuse(AddressFactory::new())
            ->create();
    }

    /**
     * @test
     */
    #[Test]
    public function it_does_nothing_if_reused_object_is_not_used(): void
    {
        ContactFactory::new()
            ->reuse(new \stdClass())
            ->create();

        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_call_reuse_multiple_times(): void
    {
        $address = AddressFactory::createOne();
        $category = CategoryFactory::createOne();

        $contact = ContactFactory::new()
            ->reuse($address)
            ->reuse($category)
            ->create();

        self::assertSame($address, $contact->getAddress());
        self::assertSame($category, $contact->getCategory());
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_call_reuse_multiple_times_with_spread_parameters(): void
    {
        $address = AddressFactory::createOne();
        $category = CategoryFactory::createOne();

        $contact = ContactFactory::new()
            ->reuse($address, $category)
            ->create();

        self::assertSame($address, $contact->getAddress());
        self::assertSame($category, $contact->getCategory());
    }

    /**
     * @test
     */
    #[Test]
    public function it_reuse_the_same_object_multiple_times(): void
    {
        $category = CategoryFactory::createOne();

        $contact = ContactFactory::new()
            ->reuse($category)
            ->create();

        self::assertSame($category, $contact->getCategory());
        self::assertSame($category, $contact->getSecondaryCategory());
    }

    /**
     * @test
     */
    #[Test]
    public function it_propagate_reused_objects(): void
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
    public function reused_object_dont_have_priority_over_states(): void
    {
        $address = AddressFactory::createOne();

        $contact = ContactFactory::new()
            ->reuse($address)
            ->create(['address' => AddressFactory::new()]);

        self::assertNotSame($address, $contact->getAddress());
    }

    /**
     * @test
     */
    #[Test]
    public function reused_object_on_interface_property(): void
    {
        $otherEntity = factory(RelationshipOnInterface\OtherEntity::class)
            ->reuse($entity = object(RelationshipOnInterface\Entity::class))
            ->create()
        ;

        self::assertSame($entity, $otherEntity->entity);
    }

    /**
     * @test
     */
    #[Test]
    public function it_can_reuse_objects_in_collection(): void
    {
        $address = AddressFactory::createOne();

        $contacts = ContactFactory::new()
            ->reuse($address)
            ->many(2)
            ->create();

        self::assertSame($address, $contacts[0]->getAddress());
        self::assertSame($address, $contacts[1]->getAddress());
    }

    /**
     * @test
     */
    #[Test]
    public function it_propagates_reused_objects_to_collection(): void
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
}
