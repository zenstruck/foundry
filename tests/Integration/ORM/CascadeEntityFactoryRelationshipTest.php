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

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\CascadeAddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CascadeCategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\CascadeContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Tag\CascadeTagFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CascadeEntityFactoryRelationshipTest extends EntityFactoryRelationshipTest
{
    /**
     * @test
     */
    public function ensure_to_one_cascade_relations_are_not_pre_persisted(): void
    {
        $contact = $this->contactFactory()
            ->afterInstantiate(function() {
                $this->categoryFactory()::repository()->assert()->empty();
                $this->addressFactory()::repository()->assert()->empty();
                $this->tagFactory()::repository()->assert()->empty();
            })
            ->create([
                'tags' => $this->tagFactory()->many(3),
                'category' => $this->categoryFactory()
            ])
        ;

        $this->assertNotNull($contact->getCategory()?->id);
        $this->assertNotNull($contact->getAddress()->id);
        $this->assertCount(3, $contact->getTags());

        foreach ($contact->getTags() as $tag) {
            $this->assertNotNull($tag->id);
        }
    }

    /**
     * @test
     */
    public function ensure_many_to_many_cascade_relations_are_not_pre_persisted(): void
    {
        $tag = $this->tagFactory()
            ->afterInstantiate(function() {
                $this->categoryFactory()::repository()->assert()->empty();
                $this->addressFactory()::repository()->assert()->empty();
                $this->contactFactory()::repository()->assert()->empty();
            })
            ->create([
                'contacts' => $this->contactFactory()->many(3),
            ])
        ;

        $this->assertCount(3, $tag->getContacts());

        foreach ($tag->getContacts() as $contact) {
            $this->assertNotNull($contact->id);
        }
    }

    /**
     * @test
     */
    public function ensure_one_to_many_cascade_relations_are_not_pre_persisted(): void
    {
        $category = $this->categoryFactory()
            ->afterInstantiate(function() {
                $this->contactFactory()::repository()->assert()->empty();
                $this->addressFactory()::repository()->assert()->empty();
                $this->tagFactory()::repository()->assert()->empty();
            })
            ->create([
                'contacts' => $this->contactFactory()->many(3),
            ])
        ;

        $this->assertCount(3, $category->getContacts());

        foreach ($category->getContacts() as $contact) {
            $this->assertNotNull($contact->id);
        }
    }

    protected function contactFactory(): PersistentObjectFactory
    {
        return CascadeContactFactory::new(); // @phpstan-ignore-line
    }

    protected function categoryFactory(): PersistentObjectFactory
    {
        return CascadeCategoryFactory::new(); // @phpstan-ignore-line
    }

    protected function tagFactory(): PersistentObjectFactory
    {
        return CascadeTagFactory::new(); // @phpstan-ignore-line
    }

    protected function addressFactory(): PersistentObjectFactory
    {
        return CascadeAddressFactory::new(); // @phpstan-ignore-line
    }
}
