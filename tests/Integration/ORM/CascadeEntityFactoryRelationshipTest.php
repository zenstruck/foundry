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
final class CascadeEntityFactoryRelationshipTest extends EntityFactoryRelationshipTestCase
{
    /**
     * @test
     */
    public function ensure_to_one_cascade_relations_are_not_pre_persisted(): void
    {
        $contact = self::contactFactory()
            ->afterInstantiate(function() {
                self::categoryFactory()::repository()->assert()->empty();
                self::addressFactory()::repository()->assert()->empty();
                self::tagFactory()::repository()->assert()->empty();
            })
            ->create([
                'tags' => self::tagFactory()->many(3),
                'category' => self::categoryFactory(),
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
        $tag = self::tagFactory()
            ->afterInstantiate(function() {
                self::categoryFactory()::repository()->assert()->empty();
                self::addressFactory()::repository()->assert()->empty();
                self::contactFactory()::repository()->assert()->empty();
            })
            ->create([
                'contacts' => self::contactFactory()->many(3),
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
        $category = self::categoryFactory()
            ->afterInstantiate(function() {
                self::contactFactory()::repository()->assert()->empty();
                self::addressFactory()::repository()->assert()->empty();
                self::tagFactory()::repository()->assert()->empty();
            })
            ->create([
                'contacts' => self::contactFactory()->many(3),
            ])
        ;

        $this->assertCount(3, $category->getContacts());

        foreach ($category->getContacts() as $contact) {
            $this->assertNotNull($contact->id);
        }
    }

    protected static function contactFactory(): PersistentObjectFactory
    {
        return CascadeContactFactory::new(); // @phpstan-ignore return.type
    }

    protected static function categoryFactory(): PersistentObjectFactory
    {
        return CascadeCategoryFactory::new(); // @phpstan-ignore return.type
    }

    protected static function tagFactory(): PersistentObjectFactory
    {
        return CascadeTagFactory::new(); // @phpstan-ignore return.type
    }

    protected static function addressFactory(): PersistentObjectFactory
    {
        return CascadeAddressFactory::new(); // @phpstan-ignore return.type
    }
}
