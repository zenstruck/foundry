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

namespace Zenstruck\Foundry\Tests\Integration\ORM\EntityRelationship;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnorePhpunitWarnings;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyGenerator;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\ChangesEntityRelationshipCascadePersist;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\UsingRelationships;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Entity\Tag;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Integration\ORM\EdgeCasesRelationshipTest;

use function Zenstruck\Foundry\lazy;
use function Zenstruck\Foundry\Persistence\flush_after;
use function Zenstruck\Foundry\Persistence\refresh;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.4
 */
#[RequiresPhpunit('>=11.4')]
abstract class EntityFactoryRelationshipTestCase extends KernelTestCase
{
    use ChangesEntityRelationshipCascadePersist, Factories, ResetDatabase;

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['category'])]
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

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts'])]
    public function one_to_many_with_factory_collection(): void
    {
        $this->one_to_many(static::contactFactory()->many(2));
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts'])]
    public function create_many_one_to_many_with_factory_collection(): void
    {
        CategoryFactory::new([
            'contacts' => ContactFactory::new()->many(5),
        ])->noRandom()->create();

        ContactFactory::assert()->count(5);
        CategoryFactory::assert()->count(1);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts'])]
    public function one_to_many_with_array_of_factories(): void
    {
        $this->one_to_many([static::contactFactory(), static::contactFactory()]);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts'])]
    public function one_to_many_with_array_of_managed_objects(): void
    {
        $this->one_to_many([static::contactFactoryWithoutCategory()->create(), static::contactFactoryWithoutCategory()->create()]);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts'])]
    #[UsingRelationships(Contact::class, ['address'])]
    public function inverse_one_to_many_relationship(): void
    {
        $category = static::categoryFactory()->create([
            'contacts' => [
                static::contactFactoryWithoutCategory(),
                static::contactFactoryWithoutCategory()->create(),
            ],
        ]);

        static::categoryFactory()::assert()->count(1);
        static::contactFactory()::assert()->count(2);

        foreach ($category->getContacts() as $contact) {
            $this->assertSame($category->id, $contact->getCategory()?->id);
        }
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Tag::class, ['contacts'])]
    public function many_to_many_owning(): void
    {
        $this->many_to_many(static::contactFactory()->many(3));
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Tag::class, ['contacts'])]
    public function many_to_many_owning_as_array(): void
    {
        $this->many_to_many([static::contactFactory(), static::contactFactory(), static::contactFactory()]);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['tags'])]
    public function many_to_many_inverse(): void
    {
        $contact = static::contactFactory()->create([
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

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['address'])]
    public function one_to_one_owning(): void
    {
        $contact = static::contactFactory()->create();

        static::contactFactory()::assert()->count(1);
        static::addressFactory()::assert()->count(1);

        $this->assertNotNull($contact->id);
        $this->assertNotNull($contact->getAddress()->id);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Address::class, ['contact'])]
    #[UsingRelationships(Contact::class, ['address', 'category'])]
    public function inversed_one_to_one(): void
    {
        $address = static::addressFactory()->create(['contact' => static::contactFactory()]);

        self::assertNotNull($address->getContact());

        static::addressFactory()::assert()->count(1);
        static::contactFactory()::assert()->count(1);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['address'])]
    public function many_to_one_unmanaged_raw_entity(): void
    {
        $address = ProxyGenerator::unwrap(static::addressFactory()->create(['city' => 'Some city']));

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        $contact = static::contactFactory()->create(['address' => $address]);

        $this->assertSame('Some city', $contact->getAddress()->getCity());
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts', 'secondaryContacts'])]
    public function one_to_many_with_two_relationships_same_entity(): void
    {
        $category = static::categoryFactory()->create([
            'contacts' => static::contactFactory()->many(2),

            // ensure no "main category" is set for secondary contacts
            'secondaryContacts' => static::contactFactoryWithoutCategory()->many(3),
        ]);

        $this->assertCount(2, $category->getContacts());
        $this->assertCount(3, $category->getSecondaryContacts());

        static::contactFactory()::assert()->count(5);
        static::categoryFactory()::assert()->count(1);

        foreach ($category->getContacts() as $contact) {
            self::assertSame(ProxyGenerator::unwrap($category), $contact->getCategory());
        }

        foreach ($category->getSecondaryContacts() as $contact) {
            self::assertSame(ProxyGenerator::unwrap($category), $contact->getSecondaryCategory());
        }
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts', 'secondaryContacts'])]
    public function one_to_many_with_two_relationships_same_entity_and_adders(): void
    {
        $category = static::categoryFactory()->create([
            'addContact' => static::contactFactoryWithoutCategory(),
            'addSecondaryContact' => static::contactFactoryWithoutCategory(),
        ]);

        $this->assertCount(1, $category->getContacts());
        $this->assertCount(1, $category->getSecondaryContacts());

        static::contactFactory()::assert()->count(2);
        static::categoryFactory()::assert()->count(1);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts', 'secondaryContacts'])]
    public function inverse_many_to_many_with_two_relationships_same_entity(): void
    {
        static::tagFactory()::assert()->count(0);

        $tag = static::tagFactory()->create([
            'contacts' => static::contactFactory()->many(3),
            'secondaryContacts' => static::contactFactory()->many(2),
        ]);

        $this->assertCount(3, $tag->getContacts());
        $this->assertCount(2, $tag->getSecondaryContacts());

        static::contactFactory()::assert()->count(5);
        static::tagFactory()::assert()->count(1);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts', 'secondaryContacts'])]
    public function can_use_adder_as_attributes(): void
    {
        $category = static::categoryFactory()->create([
            'addContact' => static::contactFactory()->with(['name' => 'foo']),
        ]);

        self::assertCount(1, $category->getContacts());
        self::assertSame('foo', $category->getContacts()[0]?->getName());
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts'])]
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
            self::assertSame(ProxyGenerator::unwrap($category), $contact->getCategory());
        }
        static::contactFactory()::assert()->count(2);
        static::categoryFactory()::assert()->count(1);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['tags', 'category'])]
    public function disabling_persistence_cascades_to_children(): void
    {
        $contact = static::contactFactory()->withoutPersisting()->create([
            'tags' => static::tagFactory()->many(3),
            'category' => static::categoryFactory(),
        ]);

        // ensure nothing was persisted in Doctrine by flushing
        self::getContainer()->get(EntityManagerInterface::class)->flush(); // @phpstan-ignore method.notFound

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
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['category'])]
    public function disabling_persistence_cascades_to_children_one_to_many(): void
    {
        $category = static::categoryFactory()->withoutPersisting()->create([
            'contacts' => static::contactFactory()->many(3),
        ]);

        // ensure nothing was persisted in Doctrine by flushing
        self::getContainer()->get(EntityManagerInterface::class)->flush(); // @phpstan-ignore method.notFound

        static::contactFactory()::assert()->empty();
        static::categoryFactory()::assert()->empty();

        $this->assertNull($category->id);
        $this->assertCount(3, $category->getContacts());

        foreach ($category->getContacts() as $contact) {
            $this->assertSame($category->getName(), $contact->getCategory()?->getName());
        }
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['address'])]
    public function disabling_persistence_cascades_to_children_inversed_one_to_one(): void
    {
        $address = static::addressFactory()->withoutPersisting()->create([
            'contact' => static::contactFactory(),
        ]);

        // ensure nothing was persisted in Doctrine by flushing
        self::getContainer()->get(EntityManagerInterface::class)->flush(); // @phpstan-ignore method.notFound

        static::contactFactory()::assert()->empty();
        static::addressFactory()::assert()->empty();

        $this->assertNull($address->id);
        $this->assertInstanceOf(Contact::class, $address->getContact());
        $this->assertNull($address->getContact()->id);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts'])]
    #[UsingRelationships(Contact::class, ['tags', 'address'])]
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

    /** @test */
    #[Test]
    public function assert_updates_are_implicitly_persisted(): void
    {
        $category = static::categoryFactory()->create();
        $address = static::addressFactory()->create();

        $category->setName('new name');

        static::contactFactory()->create(['category' => $category, 'address' => $address]);

        refresh($category);
        self::assertSame('new name', $category->getName());
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts'])]
    public function it_can_add_managed_entity_to_many_to_one(): void
    {
        $this->it_can_add_entity_to_many_to_one(
            static::categoryFactory()->create()
        );
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts'])]
    public function it_can_add_unmanaged_entity_to_many_to_one(): void
    {
        $this->it_can_add_entity_to_many_to_one(
            static::categoryFactory()->withoutPersisting()->create()
        );
    }

    /** @test */
    #[Test]
    public function it_uses_after_persist_with_many_to_many(): void
    {
        $contact = static::contactFactory()
            ->with(
                [
                    'tags' => static::tagFactory()
                        ->afterPersist(static function(Tag $tag) {$tag->setName('foobar'); })
                        ->many(1),
                ]
            )
            ->create();

        self::assertEquals('foobar', $contact->getTags()[0]?->getName());
    }

    /** @test */
    #[Test]
    public function it_uses_after_persist_with_one_to_many(): void
    {
        $category = static::categoryFactory()
            ->with([
                'contacts' => static::contactFactory()
                    ->afterPersist(static function(Contact $contact) {
                        $contact->setName('foobar');
                    })
                    ->many(1),
            ])->create();

        self::assertEquals('foobar', $category->getContacts()[0]?->getName());
    }

    /** @test */
    #[Test]
    public function it_uses_after_persist_with_many_to_one(): void
    {
        $contact = static::contactFactory()
            ->with([
                'category' => static::categoryFactory()
                    ->afterPersist(static function(Category $category) {
                        $category->setName('foobar');
                    }),
            ])->create();

        self::assertEquals('foobar', $contact->getCategory()?->getName());
    }

    /** @test */
    #[Test]
    public function it_uses_after_persist_with_one_to_one(): void
    {
        $contact = static::contactFactory()
            ->with([
                'address' => static::addressFactory()
                    ->afterPersist(static function(Address $address) {$address->setCity('foobar'); }),
            ])->create();

        self::assertEquals('foobar', $contact->getAddress()->getCity());
    }

    /** @test */
    #[Test]
    public function it_uses_after_persist_with_inversed_one_to_one(): void
    {
        $address = static::addressFactory()
            ->with([
                'contact' => static::contactFactory()
                    ->afterPersist(static function(Contact $contact) {$contact->setName('foobar'); }),
            ])->create();

        self::assertEquals('foobar', $address->getContact()?->getName());
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['category'])]
    public function can_call_create_in_after_persist_callback(): void
    {
        $category = static::categoryFactory()::new()
            ->afterPersist(function(Category $category) {
                static::contactFactory()->create(['category' => $category]);
            })
            ->create();

        static::categoryFactory()::assert()->count(1);
        static::contactFactory()::assert()->count(1);
        self::assertCount(1, $category->getContacts());
        self::assertSame(ProxyGenerator::unwrap($category), $category->getContacts()[0]?->getCategory());
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['address'])]
    public function can_use_nested_after_persist_callback(): void
    {
        $contact = static::contactFactory()::createOne(
            [
                'address' => static::addressFactory()
                    ->afterPersist(function(Address $address) {
                        $address->setCity('city from after persist');
                    }),
            ]
        );

        self::assertSame('city from after persist', $contact->getAddress()->getCity());
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['category'])]
    public function can_call_create_in_nested_after_persist_callback(): void
    {
        $contact = static::contactFactory()::createOne(
            [
                'category' => static::categoryFactory()
                    ->afterPersist(function(Category $category) {
                        $category->addSecondaryContact(
                            ProxyGenerator::unwrap(static::contactFactory()::createOne())
                        );
                    }),
            ]
        );

        self::assertCount(1, $contact->getCategory()?->getSecondaryContacts() ?? []);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Address::class, ['contact'])]
    #[UsingRelationships(Contact::class, ['category'])]
    public function inverse_one_to_one_with_flush_in_before_instantiate(): void
    {
        $address = static::addressFactory()::createOne(
            [
                'contact' => static::contactFactory()
                    ->beforeInstantiate(
                        function(array $attributes): array {
                            $attributes['category'] = static::categoryFactory()->create();

                            return $attributes;
                        }
                    ),
            ]
        );

        static::addressFactory()::assert()->count(1);
        static::contactFactory()::assert()->count(1);
        static::categoryFactory()::assert()->count(1);

        self::assertNotNull($address->getContact());
        self::assertNotNull($address->getContact()->getCategory());
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Address::class, ['contact'])]
    #[UsingRelationships(Contact::class, ['category'])]
    public function inverse_one_to_one_with_lazy_flush(): void
    {
        $address = static::addressFactory()::createOne(
            [
                'contact' => static::contactFactory()->with([
                    'category' => lazy(fn() => static::categoryFactory()->create()),
                ]),
            ]
        );

        static::addressFactory()::assert()->count(1);
        static::contactFactory()::assert()->count(1);
        static::categoryFactory()::assert()->count(1);

        self::assertNotNull($address->getContact());
        self::assertNotNull($address->getContact()->getCategory());
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['category'])]
    public function after_instantiate_flushing_using_current_object_in_relationship_many_to_one(): void
    {
        $category = static::categoryFactory()
            ->afterInstantiate(
                static function(Category $c): void {
                    static::contactFactory()->create(['category' => $c]);
                }
            )
            ->create();

        static::contactFactory()::assert()->count(1);
        static::categoryFactory()::assert()->count(1);

        self::assertCount(1, $category->getContacts());
        self::assertNotNull($category->getContacts()[0] ?? null);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['category'])]
    public function after_instantiate_flushing_using_current_object_in_relationship_multiple_many_to_one(): void
    {
        $category = static::categoryFactory()
            ->afterInstantiate(
                static function(Category $c): void {
                    static::contactFactory()->create(['category' => $c]);
                    static::contactFactory()->create(['category' => $c]);
                }
            )
            ->create();

        static::contactFactory()::assert()->count(2);
        static::categoryFactory()::assert()->count(1);

        self::assertCount(2, $category->getContacts());
        self::assertNotNull($category->getContacts()[0] ?? null);
        self::assertNotNull($category->getContacts()[1] ?? null);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['category'])]
    public function after_instantiate_flushing_using_current_object_in_relationship_one_to_many(): void
    {
        $contact = static::contactFactory()
            ->afterInstantiate(
                static function(Contact $c): void {
                    static::categoryFactory()->create(['contacts' => [$c]]);
                }
            )
            ->create(['category' => null]);

        static::contactFactory()::assert()->count(1);
        static::categoryFactory()::assert()->count(1);

        self::assertNotNull($contact->getCategory());
        self::assertCount(1, $contact->getCategory()->getContacts());
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['category'])]
    public function after_instantiate_flushing_using_current_object_in_relationship_one_to_one(): void
    {
        $address = static::addressFactory()
            ->afterInstantiate(
                static function(Address $a): void {
                    static::contactFactory()->create(['address' => $a]);
                }
            )->create();

        self::assertNotNull($address->getContact());
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['category'])]
    public function find_or_create_used_in_many_actually_create_only_one_object(): void
    {
        static::contactFactory()
            ->many(2)
            ->create(['category' => lazy(fn() => static::categoryFactory()::findOrCreate([]))]);

        static::contactFactory()::assert()->count(2);
        static::categoryFactory()::assert()->count(1);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['category'])]
    public function ensure_inverse_one_to_many_is_hydrated_before_persisting_in_flush_after(): void
    {
        flush_after(
            static function() {
                $contactFactory = static::contactFactory()
                    ->afterPersist(static function(Contact $contact) {
                        self::assertNotNull($contact->getCategory());
                    });

                static::categoryFactory()::createMany(
                    2,
                    ['contacts' => lazy(static fn() => $contactFactory->many(2)->create(['category' => null]))]
                );
            }
        );

        static::contactFactory()::assert()->count(4);
        static::categoryFactory()::assert()->count(2);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Contact::class, ['category'])]
    public function call_many_with_zero_do_nothing(): void
    {
        static::contactFactory()
            ->many(0)
            ->create();

        static::contactFactory()::assert()->count(0);
    }

    /**
     * @test
     * @dataProvider provideCanUseFactoryInDataProviderWithRelationshipCases
     * @param PersistentObjectFactory<Contact> $factory
     */
    #[Test]
    #[DataProvider('provideCanUseFactoryInDataProviderWithRelationshipCases')]
    public function can_create_many_with_factory_from_data_provider_with_relationship(PersistentObjectFactory $factory): void
    {
        $objects = $factory->many(2)->create();

        self::assertCount(2, $objects);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[IgnorePhpunitWarnings(EdgeCasesRelationshipTest::DATA_PROVIDER_WARNING_REGEX)]
    #[UsingRelationships(Category::class, ['contacts'])]
    public function it_sets_one_to_many_before_after_instantiate(): void
    {
        static::categoryFactory()
            ->afterInstantiate(function(Category $category) {
                self::assertCount(3, $category->getContacts());
            })
            ->create([
                'contacts' => static::contactFactory()->many(3),
            ])
        ;
    }

    public static function provideCanUseFactoryInDataProviderWithRelationshipCases(): iterable
    {
        yield [
            static::contactFactory(),
        ];
    }

    /** @return PersistentObjectFactory<Contact> */
    protected static function contactFactoryWithoutCategory(): PersistentObjectFactory
    {
        return static::contactFactory()->with(['category' => null]);
    }

    /** @return PersistentObjectFactory<Contact> */
    abstract protected static function contactFactory(): PersistentObjectFactory;

    /** @return PersistentObjectFactory<Category> */
    abstract protected static function categoryFactory(): PersistentObjectFactory;

    /** @return PersistentObjectFactory<Tag> */
    abstract protected static function tagFactory(): PersistentObjectFactory;

    /** @return PersistentObjectFactory<Address> */
    abstract protected static function addressFactory(): PersistentObjectFactory;

    private function it_can_add_entity_to_many_to_one(Category $category): void
    {
        self::assertCount(0, $category->getContacts());

        $contact1 = static::contactFactory()->create(['category' => $category]);
        $contact2 = static::contactFactory()->create(['category' => $category]);

        static::categoryFactory()::assert()->count(1);

        self::assertCount(2, $category->getContacts());

        self::assertSame(ProxyGenerator::unwrap($category), $contact1->getCategory());
        self::assertSame(ProxyGenerator::unwrap($category), $contact2->getCategory());
    }

    /**
     * @param FactoryCollection<Contact, PersistentObjectFactory<Contact>>|list<Factory<Contact>>|list<Contact> $contacts
     */
    private function one_to_many(FactoryCollection|array $contacts): void
    {
        $category = static::categoryFactory()->create([
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
     * @param FactoryCollection<Contact, PersistentObjectFactory<Contact>>|list<Factory<Contact>>|list<Contact> $contacts
     */
    private function many_to_many(FactoryCollection|array $contacts): void
    {
        $tag = static::tagFactory()->create([
            'contacts' => $contacts,
        ]);

        static::contactFactory()::assert()->count(3);
        static::tagFactory()::repository()->assert()->count(1);
        $this->assertNotNull($tag->id);

        foreach ($tag->getContacts() as $contact) {
            $this->assertSame($tag->id, $contact->getTags()[0]?->id);
        }
    }
}
