<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Entity\Tag;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\unproxy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class EntityFactoryRelationshipTestCase extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    /**
     * @test
     */
    public function many_to_one(): void
    {
        $contact = static::contactFactory()::createOne([
            'category' => static::categoryFactory(),
        ]);

        static::contactFactory()::repository()->assert()->count(1);
        static::categoryFactory()::repository()->assert()->count(1);

        $this->assertNotNull($contact->id);
        $this->assertNotNull($contact->getCategory()?->id);
    }

    /**
     * @test
     */
    public function disabling_persistence_cascades_to_children(): void
    {
        $contact = static::contactFactory()->withoutPersisting()->create([
            'tags' => static::tagFactory()->many(3),
            'category' => static::categoryFactory(),
        ]);

        static::contactFactory()::repository()->assert()->empty();
        static::categoryFactory()::repository()->assert()->empty();
        static::tagFactory()::repository()->assert()->empty();
        static::addressFactory()::repository()->assert()->empty();

        $this->assertNull($contact->id);
        $this->assertNull($contact->getCategory()?->id);
        $this->assertNull($contact->getAddress()->id);
        $this->assertCount(3, $contact->getTags());

        foreach ($contact->getTags() as $tag) {
            $this->assertNull($tag->id);
        }

        $category = static::categoryFactory()->withoutPersisting()->create([
            'contacts' => static::contactFactory()->many(3),
        ]);

        static::contactFactory()::repository()->assert()->empty();
        static::categoryFactory()::repository()->assert()->empty();

        $this->assertNull($category->id);
        $this->assertCount(3, $category->getContacts());

        foreach ($category->getContacts() as $contact) {
            $this->assertSame($category->getName(), $contact->getCategory()?->getName());
        }
    }

    /**
     * @test
     * @param FactoryCollection<Contact>|list<Factory<Contact>> $contacts
     * @dataProvider one_to_many_provider
     */
    public function one_to_many(FactoryCollection|array $contacts): void
    {
        $category = static::categoryFactory()::createOne([
            'contacts' => $contacts,
        ]);

        static::contactFactory()::repository()->assert()->count(2);
        static::categoryFactory()::repository()->assert()->count(1);
        $this->assertNotNull($category->id);
        $this->assertCount(2, $category->getContacts());

        foreach ($category->getContacts() as $contact) {
            $this->assertSame($category->id, $contact->getCategory()?->id);
        }
    }

    public static function one_to_many_provider(): iterable
    {
        yield 'as a factory collection' => [static::contactFactory()->many(2)];
        yield 'as an array of factories' => [[static::contactFactory(), static::contactFactory()]];
    }

    /**
     * @test
     */
    public function inverse_one_to_many_relationship(): void
    {
        static::categoryFactory()::assert()->count(0);
        static::contactFactory()::assert()->count(0);

        $category = static::categoryFactory()->create([
            'contacts' => [
                static::contactFactory()->with(['category' => null]),
                static::contactFactory()->create(['category' => null]),
            ],
        ]);

        static::categoryFactory()::assert()->count(1);
        static::contactFactory()::assert()->count(2);

        foreach ($category->getContacts() as $contact) {
            $this->assertSame($category->id, $contact->getCategory()?->id);
        }
    }

    /**
     * @test
     */
    public function many_to_many_owning(): void
    {
        $tag = static::tagFactory()::createOne([
            'contacts' => static::contactFactory()->many(3),
        ]);

        static::contactFactory()::repository()->assert()->count(3);
        static::tagFactory()::repository()->assert()->count(1);
        $this->assertNotNull($tag->id);

        foreach ($tag->getContacts() as $contact) {
            $this->assertSame($tag->id, $contact->getTags()[0]?->id);
        }
    }

    /**
     * @test
     */
    public function many_to_many_owning_as_array(): void
    {
        $tag = static::tagFactory()::createOne([
            'contacts' => [static::contactFactory(), static::contactFactory(), static::contactFactory()],
        ]);

        static::contactFactory()::repository()->assert()->count(3);
        static::tagFactory()::repository()->assert()->count(1);
        $this->assertNotNull($tag->id);

        foreach ($tag->getContacts() as $contact) {
            $this->assertSame($tag->id, $contact->getTags()[0]?->id);
        }
    }

    /**
     * @test
     */
    public function many_to_many_inverse(): void
    {
        $contact = static::contactFactory()::createOne([
            'tags' => static::tagFactory()->many(3),
        ]);

        static::contactFactory()::repository()->assert()->count(1);
        static::tagFactory()::repository()->assert()->count(3);
        $this->assertNotNull($contact->id);

        foreach ($contact->getTags() as $tag) {
            $this->assertTrue($contact->getTags()->contains($tag));
            $this->assertNotNull($tag->id);
        }
    }

    /**
     * @test
     */
    public function one_to_one_owning(): void
    {
        $contact = static::contactFactory()::createOne();

        static::contactFactory()::repository()->assert()->count(1);
        static::addressFactory()::repository()->assert()->count(1);

        $this->assertNotNull($contact->id);
        $this->assertNotNull($contact->getAddress()->id);
    }

    /**
     * @test
     */
    public function many_to_one_unmanaged_raw_entity(): void
    {
        $address = unproxy(static::addressFactory()->create(['city' => 'Some city']));

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        $contact = static::contactFactory()->create(['address' => $address]);

        $this->assertSame('Some city', $contact->getAddress()->getCity());
    }

    /**
     * @test
     */
    public function one_to_many_with_two_relationships_same_entity(): void
    {
        $category = static::categoryFactory()->create([
            'contacts' => static::contactFactory()->many(2),
            'secondaryContacts' => static::contactFactory()
                ->with(['category' => null]) // ensure no "main category" is set for secondary contacts
                ->many(3),
        ]);

        $this->assertCount(2, $category->getContacts());
        $this->assertCount(3, $category->getSecondaryContacts());
        static::contactFactory()::assert()->count(5);
        static::categoryFactory()::assert()->count(1);

        foreach ($category->getContacts() as $contact) {
            self::assertSame(unproxy($category), $contact->getCategory());
        }

        foreach ($category->getSecondaryContacts() as $contact) {
            self::assertSame(unproxy($category), $contact->getSecondaryCategory());
        }
    }

    /**
     * @test
     */
    public function one_to_many_with_two_relationships_same_entity_and_adders(): void
    {
        $category = static::categoryFactory()->create([
            'addContact' => static::contactFactory()->with(['category' => null]),
            'addSecondaryContact' => static::contactFactory()->with(['category' => null]),
        ]);

        $this->assertCount(1, $category->getContacts());
        $this->assertCount(1, $category->getSecondaryContacts());
        static::contactFactory()::assert()->count(2);
        static::categoryFactory()::assert()->count(1);
    }

    /**
     * @test
     */
    public function inverse_many_to_many_with_two_relationships_same_entity(): void
    {
        static::tagFactory()::assert()->count(0);

        $tag = static::tagFactory()->create([
            'contacts' => static::contactFactory()->many(3),
            'secondaryContacts' => static::contactFactory()->many(3),
        ]);

        $this->assertCount(3, $tag->getContacts());
        $this->assertCount(3, $tag->getSecondaryContacts());
        static::tagFactory()::assert()->count(1);
        static::contactFactory()::assert()->count(6);
    }

    /**
     * @test
     */
    public function inversed_one_to_one(): void
    {
        $addressFactory = $this->addressFactory();
        $contactFactory = $this->contactFactory();

        $address = $addressFactory->create(['contact' => $contactFactory]);

        self::assertNotNull($address->getContact());

        $addressFactory::assert()->count(1);
        $contactFactory::assert()->count(1);
    }

    /**
     * @test
     */
    public function can_use_adder_as_attributes(): void
    {
        $category = static::categoryFactory()->create([
            'addContact' => static::contactFactory()->with(['name' => 'foo']),
        ]);

        self::assertCount(1, $category->getContacts());
        self::assertSame('foo', $category->getContacts()[0]?->getName());
    }

    /**
     * @test
     */
    public function forced_one_to_many_with_doctrine_collection_type(): void
    {
        $category = static::categoryFactory()
            ->instantiateWith(Instantiator::withConstructor()->alwaysForce())
            ->create([
                'contacts' => static::contactFactory()->many(2),
            ])
        ;

        self::assertCount(2, $category->getContacts());
        foreach ($category->getContacts() as $contact) {
            self::assertSame(unproxy($category), $contact->getCategory());
        }
        static::contactFactory()::assert()->count(2);
        static::categoryFactory()::assert()->count(1);
    }

    /**
     * @test
     */
    public function ensure_one_to_many_cascade_relations_are_not_pre_persisted(): void
    {
        $category = static::categoryFactory()
            ->afterInstantiate(function() {
                static::contactFactory()::repository()->assert()->empty();
                static::addressFactory()::repository()->assert()->empty();
                static::tagFactory()::repository()->assert()->empty();
            })
            ->create([
                'contacts' => static::contactFactory()->many(3),
            ])
        ;

        $this->assertCount(3, $category->getContacts());

        foreach ($category->getContacts() as $contact) {
            $this->assertNotNull($contact->id);
        }
    }

    /**
     * @return PersistentObjectFactory<Contact>
     */
    abstract protected static function contactFactory(): PersistentObjectFactory;

    /**
     * @return PersistentObjectFactory<Category>
     */
    abstract protected static function categoryFactory(): PersistentObjectFactory;

    /**
     * @return PersistentObjectFactory<Tag>
     */
    abstract protected static function tagFactory(): PersistentObjectFactory;

    /**
     * @return PersistentObjectFactory<Address>
     */
    abstract protected static function addressFactory(): PersistentObjectFactory;
}
