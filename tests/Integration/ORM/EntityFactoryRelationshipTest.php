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
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Entity\Tag;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\StandardAddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\StandardCategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\StandardContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\EdgeCases\MultipleMandatoryRelationshipToSameEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\EdgeCases\RichDomainMandatoryRelationship;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Tag\StandardTagFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\unproxy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class EntityFactoryRelationshipTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    /**
     * @test
     */
    public function many_to_one(): void
    {
        $contact = $this->contactFactory()::createOne([
            'category' => $this->categoryFactory()
        ]);

        $this->contactFactory()::repository()->assert()->count(1);
        $this->categoryFactory()::repository()->assert()->count(1);

        $this->assertNotNull($contact->id);
        $this->assertNotNull($contact->getCategory()?->id);
    }

    /**
     * @test
     */
    public function disabling_persistence_cascades_to_children(): void
    {
        $contact = $this->contactFactory()->withoutPersisting()->create([
            'tags' => $this->tagFactory()->many(3),
            'category' => $this->categoryFactory()
        ]);

        $this->contactFactory()::repository()->assert()->empty();
        $this->categoryFactory()::repository()->assert()->empty();
        $this->tagFactory()::repository()->assert()->empty();
        $this->addressFactory()::repository()->assert()->empty();

        $this->assertNull($contact->id);
        $this->assertNull($contact->getCategory()?->id);
        $this->assertNull($contact->getAddress()->id);
        $this->assertCount(3, $contact->getTags());

        foreach ($contact->getTags() as $tag) {
            $this->assertNull($tag->id);
        }

        $category = $this->categoryFactory()->withoutPersisting()->create([
            'contacts' => $this->contactFactory()->many(3),
        ]);

        $this->contactFactory()::repository()->assert()->empty();
        $this->categoryFactory()::repository()->assert()->empty();

        $this->assertNull($category->id);
        $this->assertCount(3, $category->getContacts());

        foreach ($category->getContacts() as $contact) {
            $this->assertSame($category->getName(), $contact->getCategory()?->getName());
        }
    }

    /**
     * @test
     */
    public function one_to_many(): void
    {
        $category = $this->categoryFactory()::createOne([
            'contacts' => $this->contactFactory()->many(3),
        ]);

        $this->contactFactory()::repository()->assert()->count(3);
        $this->categoryFactory()::repository()->assert()->count(1);
        $this->assertNotNull($category->id);
        $this->assertCount(3, $category->getContacts());

        foreach ($category->getContacts() as $contact) {
            $this->assertSame($category->id, $contact->getCategory()?->id);
        }
    }

    /**
     * @test
     */
    public function many_to_many_owning(): void
    {
        $tag = $this->tagFactory()::createOne([
            'contacts' => $this->contactFactory()->many(3),
        ]);

        $this->contactFactory()::repository()->assert()->count(3);
        $this->tagFactory()::repository()->assert()->count(1);
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
        $tag = $this->tagFactory()::createOne([
            'contacts' => [$this->contactFactory(), $this->contactFactory(), $this->contactFactory()],
        ]);

        $this->contactFactory()::repository()->assert()->count(3);
        $this->tagFactory()::repository()->assert()->count(1);
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
        $contact = $this->contactFactory()::createOne([
            'tags' => $this->tagFactory()->many(3),
        ]);

        $this->contactFactory()::repository()->assert()->count(1);
        $this->tagFactory()::repository()->assert()->count(3);
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
        $contact = $this->contactFactory()::createOne();

        $this->contactFactory()::repository()->assert()->count(1);
        $this->addressFactory()::repository()->assert()->count(1);

        $this->assertNotNull($contact->id);
        $this->assertNotNull($contact->getAddress()->id);
    }

    /**
     * @test
     */
    public function one_to_one_inverse(): void
    {
        $this->markTestSkipped('Not supported. Should it be?');
    }

    /**
     * @test
     */
    public function many_to_one_unmanaged_raw_entity(): void
    {
        $address = unproxy($this->addressFactory()->create(['city' => 'Some city']));

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();

        $contact = $this->contactFactory()->create(['address' => $address]);

        $this->assertSame('Some city', $contact->getAddress()->getCity());
    }

    /**
     * @test
     */
    public function inverse_one_to_many_relationship(): void
    {
        $this->categoryFactory()::assert()->count(0);
        $this->contactFactory()::assert()->count(0);

        $this->categoryFactory()->create([
            'contacts' => [
                $this->contactFactory(),
                $this->contactFactory()->create(),
            ],
        ]);

        $this->categoryFactory()::assert()->count(1);
        $this->contactFactory()::assert()->count(2);
    }

    /**
     * @test
     */
    public function one_to_many_with_two_relationships_same_entity(): void
    {
        $category = $this->categoryFactory()->create([
            'contacts' => $this->contactFactory()->many(4),
            'secondaryContacts' => $this->contactFactory()->many(4),
        ]);

        $this->assertCount(4, $category->getContacts());
        $this->assertCount(4, $category->getSecondaryContacts());
        $this->contactFactory()::assert()->count(8);
        $this->categoryFactory()::assert()->count(1);
    }

    /**
     * @test
     */
    public function inverse_many_to_many_with_two_relationships_same_entity(): void
    {
        $this->tagFactory()::assert()->count(0);

        $tag = $this->tagFactory()->create([
            'contacts' => $this->contactFactory()->many(3),
            'secondaryContacts' => $this->contactFactory()->many(3),
        ]);

        $this->assertCount(3, $tag->getContacts());
        $this->assertCount(3, $tag->getSecondaryContacts());
        $this->tagFactory()::assert()->count(1);
        $this->contactFactory()::assert()->count(6);
    }

    /**
     * @test
     */
    public function can_use_adder_as_attributes(): void
    {
        $category = $this->categoryFactory()->create([
            'addContact' => $this->contactFactory()->with(['name' => 'foo'])
        ]);

        self::assertCount(1, $category->getContacts());
        self::assertSame('foo', $category->getContacts()[0]?->getName());
    }

    /**
     * @test
     */
    public function one_to_many_with_two_relationships_same_entity_and_adders(): void
    {
        $category = $this->categoryFactory()->create([
            'addContact' => $this->contactFactory(),
            'addSecondaryContact' => $this->contactFactory(),
        ]);

        $this->assertCount(1, $category->getContacts());
        $this->assertCount(1, $category->getSecondaryContacts());
        $this->contactFactory()::assert()->count(2);
        $this->categoryFactory()::assert()->count(1);
    }

    /**
     * @test
     */
    public function inversed_multiple_mandatory_relationship_to_same_entity(): void
    {
        $this->markTestIncomplete('fixme! 🙏');

        // @phpstan-ignore-next-line
        $inversedSideEntity = MultipleMandatoryRelationshipToSameEntity\InversedSideEntityFactory::createOne([
            'mainRelations' => MultipleMandatoryRelationshipToSameEntity\OwningSideEntityFactory::new()->many(2),
            'secondaryRelations' => MultipleMandatoryRelationshipToSameEntity\OwningSideEntityFactory::new()->many(2),
        ]);

        $this->assertCount(2, $inversedSideEntity->getMainRelations());
        $this->assertCount(2, $inversedSideEntity->getSecondaryRelations());
        MultipleMandatoryRelationshipToSameEntity\OwningSideEntityFactory::assert()->count(4);
        MultipleMandatoryRelationshipToSameEntity\InversedSideEntityFactory::assert()->count(1);
    }

    /**
     * @test
     */
    public function inversed_mandatory_relationship_in_rich_domain(): void
    {
        $this->markTestIncomplete('fixme! 🙏');

        // @phpstan-ignore-next-line
        $inversedSideEntity = RichDomainMandatoryRelationship\InversedSideEntityFactory::createOne([
            'main' => RichDomainMandatoryRelationship\OwningSideEntityFactory::new()->many(2),
        ]);

        $this->assertCount(2, $inversedSideEntity->getRelations());
        RichDomainMandatoryRelationship\OwningSideEntityFactory::assert()->count(2);
        RichDomainMandatoryRelationship\InversedSideEntityFactory::assert()->count(1);
    }

    /**
     * @return PersistentObjectFactory<Contact>
     */
    protected function contactFactory(): PersistentObjectFactory
    {
        return StandardContactFactory::new(); // @phpstan-ignore-line
    }

    /**
     * @return PersistentObjectFactory<Category>
     */
    protected function categoryFactory(): PersistentObjectFactory
    {
        return StandardCategoryFactory::new(); // @phpstan-ignore-line
    }

    /**
     * @return PersistentObjectFactory<Tag>
     */
    protected function tagFactory(): PersistentObjectFactory
    {
        return StandardTagFactory::new(); // @phpstan-ignore-line
    }

    /**
     * @return PersistentObjectFactory<Address>
     */
    protected function addressFactory(): PersistentObjectFactory
    {
        return StandardAddressFactory::new(); // @phpstan-ignore-line
    }
}
