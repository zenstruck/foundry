<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\ORM\EntityRelationship;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\UsingRelationships;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\WithEntityRelationship;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address\StandardAddress;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category\StandardCategory;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact\StandardContact;
use Zenstruck\Foundry\Tests\Fixture\Entity\Tag;
use Zenstruck\Foundry\Tests\Fixture\Entity\Tag\StandardTag;

use function Zenstruck\Foundry\Persistence\unproxy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
abstract class EntityFactoryRelationshipTestCase extends KernelTestCase
{
    use WithEntityRelationship, Factories, ResetDatabase;

    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardContact::class, ['category'])]
    public function many_to_one(): void
    {
        $contact = static::contactFactory()->create([
            'category' => static::categoryFactory(),
        ]);

        static::contactFactory()::assert()->count(1);
        static::categoryFactory()::assert()->count(1);

        $this->assertNotNull($contact->id);
        $this->assertNotNull($contact->getCategory()?->id);
    }

    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardCategory::class, ['contacts'])]
    public function one_to_many_with_factory_collection(): void
    {
        $this->one_to_many(static::contactFactory()->many(2));
    }

    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardCategory::class, ['contacts'])]
    public function one_to_many_with_array_of_factories(): void
    {
        $this->one_to_many([static::contactFactory(), static::contactFactory()]);
    }

    /**
     * @param FactoryCollection<Contact>|list<Factory> $contacts
     */
    private function one_to_many(FactoryCollection|array $contacts): void // @phpstan-ignore missingType.generics
    {
        $category = static::categoryFactory()::createOne([
            'contacts' => $contacts,
        ]);

        static::contactFactory()::assert()->count(2);
        static::categoryFactory()::assert()->count(1);

        $this->assertNotNull($category->id);
        $this->assertCount(2, $category->getContacts());

        foreach ($category->getContacts() as $contact) {
            $this->assertSame($category->id, $contact->getCategory()?->id);
        }
    }

    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardCategory::class, ['contacts'])]
    #[UsingRelationships(StandardContact::class, ['address'])]
    public function inverse_one_to_many_relationship(): void
    {
        static::categoryFactory()::assert()->count(0);
        static::contactFactory()::assert()->count(0);

        $category = static::categoryFactory()::createOne([
            'contacts' => [
                static::contactFactory()->with(['category' => null]),
                static::contactFactory()::createOne(['category' => null]),
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
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardTag::class, ['contacts'])]
    public function many_to_many_owning(): void
    {
        $tag = static::tagFactory()::createOne([
            'contacts' => static::contactFactory()->many(3),
        ]);

        static::contactFactory()::assert()->count(3);
        static::tagFactory()::assert()->count(1);

        $this->assertNotNull($tag->id);

        foreach ($tag->getContacts() as $contact) {
            $this->assertSame($tag->id, $contact->getTags()[0]?->id);
        }
    }

    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardTag::class, ['contacts'])]
    public function many_to_many_owning_as_array(): void
    {
        $tag = static::tagFactory()::createOne([
            'contacts' => [static::contactFactory(), static::contactFactory(), static::contactFactory()],
        ]);

        static::contactFactory()::assert()->count(3);
        static::tagFactory()::repository()->assert()->count(1);
        $this->assertNotNull($tag->id);

        foreach ($tag->getContacts() as $contact) {
            $this->assertSame($tag->id, $contact->getTags()[0]?->id);
        }
    }


    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardContact::class, ['tags'])]
    public function many_to_many_inverse(): void
    {
        $contact = static::contactFactory()::createOne([
            'tags' => static::tagFactory()::new()->many(3),
        ]);

        static::contactFactory()::assert()->count(1);
        static::tagFactory()::assert()->count(3);

        $this->assertNotNull($contact->id);

        foreach ($contact->getTags() as $tag) {
            $this->assertTrue($contact->getTags()->contains($tag));
            $this->assertNotNull($tag->id);
        }
    }


    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardContact::class, ['address'])]
    public function one_to_one_owning(): void
    {
        $contact = static::contactFactory()::createOne();

        static::contactFactory()::assert()->count(1);
        static::addressFactory()::assert()->count(1);

        $this->assertNotNull($contact->id);
        $this->assertNotNull($contact->getAddress()->id);
    }


    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardAddress::class, ['contact'])]
    #[UsingRelationships(StandardContact::class, ['address'])]
    public function inversed_one_to_one(): void
    {
        $address = static::addressFactory()::createOne(['contact' => static::contactFactory()]);

        self::assertNotNull($address->getContact());

        static::addressFactory()::assert()->count(1);
        static::contactFactory()::assert()->count(1);
    }


    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardContact::class, ['address'])]
    public function many_to_one_unmanaged_raw_entity(): void
    {
        $address = unproxy(static::addressFactory()::createOne(['city' => 'Some city']));

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        $contact = static::contactFactory()::createOne(['address' => $address]);

        $this->assertSame('Some city', $contact->getAddress()->getCity());
    }


    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardCategory::class, ['contacts', 'secondaryContacts'])]
    public function one_to_many_with_two_relationships_same_entity(): void
    {
        $category = static::categoryFactory()::createOne([
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
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardCategory::class, ['contacts', 'secondaryContacts'])]
    public function one_to_many_with_two_relationships_same_entity_and_adders(): void
    {
        $category = static::categoryFactory()::createOne([
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
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardCategory::class, ['contacts', 'secondaryContacts'])]
    public function inverse_many_to_many_with_two_relationships_same_entity(): void
    {
        static::tagFactory()::assert()->count(0);

        $tag = static::tagFactory()::createOne([
            'contacts' => static::contactFactory()->many(3),
            'secondaryContacts' => static::contactFactory()->many(2),
        ]);

        $this->assertCount(3, $tag->getContacts());
        $this->assertCount(2, $tag->getSecondaryContacts());

        static::contactFactory()::assert()->count(5);
        static::tagFactory()::assert()->count(1);
    }


    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardCategory::class, ['contacts', 'secondaryContacts'])]
    public function can_use_adder_as_attributes(): void
    {
        $category = static::categoryFactory()::createOne([
            'addContact' => static::contactFactory()->with(['name' => 'foo']),
        ]);

        self::assertCount(1, $category->getContacts());
        self::assertSame('foo', $category->getContacts()[0]?->getName());
    }


    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardCategory::class, ['contacts'])]
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
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardContact::class, ['tags', 'category'])]
    public function disabling_persistence_cascades_to_children(): void
    {
        $contact = static::contactFactory()->withoutPersisting()->create([
            'tags' => static::tagFactory()::new()->many(3),
            'category' => static::categoryFactory(),
        ]);

        static::contactFactory()::assert()->empty();
        static::categoryFactory()::assert()->empty();
        static::tagFactory()::assert()->empty();
        static::addressFactory()::assert()->empty();

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

        static::contactFactory()::assert()->empty();
        static::categoryFactory()::assert()->empty();

        $this->assertNull($category->id);
        $this->assertCount(3, $category->getContacts());

        foreach ($category->getContacts() as $contact) {
            $this->assertSame($category->getName(), $contact->getCategory()?->getName());
        }
    }

    /**
     * @test
     * @dataProvider provideCascadeRelationshipsCombinationV9
     */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombination')]
    #[UsingRelationships(StandardCategory::class, ['contacts'])]
    #[UsingRelationships(StandardContact::class, ['tags', 'address'])]
    public function ensure_one_to_many_relations_are_not_pre_persisted(): void
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
