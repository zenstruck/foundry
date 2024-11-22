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
use Doctrine\Persistence\Proxy as DoctrineProxy;
use PHPUnit\Framework\AssertionFailedError;
use Zenstruck\Assert;
use Zenstruck\Foundry\Persistence\Exception\RefreshObjectFailed;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Entity\Tag;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @method PersistentProxyObjectFactory<Contact>  contactFactory()
 * @method PersistentProxyObjectFactory<Category> categoryFactory()
 * @method PersistentProxyObjectFactory<Tag>      tagFactory()
 * @method PersistentProxyObjectFactory<Address>  addressFactory()
 */
abstract class ProxyEntityFactoryRelationshipTestCase extends EntityFactoryRelationshipTestCase
{
    /**
     * @see https://github.com/zenstruck/foundry/issues/42
     *
     * @test
     */
    public function doctrine_proxies_are_converted_to_foundry_proxies(): void
    {
        static::contactFactory()->create(['category' => static::categoryFactory()]);

        // clear the em so nothing is tracked
        self::getContainer()->get(EntityManagerInterface::class)->clear(); // @phpstan-ignore method.nonObject

        // load a random Contact which causes the em to track a "doctrine proxy" for category
        static::contactFactory()::random();

        // load a random Category which should be a "doctrine proxy"
        $category = static::categoryFactory()::random();

        // ensure the category is a "doctrine proxy" and a Category
        $this->assertInstanceOf(Proxy::class, $category);
        $this->assertInstanceOf(DoctrineProxy::class, $category->_real());
        $this->assertInstanceOf(static::categoryFactory()::class(), $category);
    }

    /**
     * @test
     */
    public function it_can_add_proxy_to_many_to_one(): void
    {
        $contact = static::contactFactory()->create();

        $contact->setCategory($category = static::categoryFactory()->create());
        $contact->_save();

        static::contactFactory()::assert()->count(1);
        static::contactFactory()::assert()->exists(['category' => $category]);
    }

    /**
     * @test
     */
    public function it_can_add_proxy_to_one_to_many(): void
    {
        $contact = static::contactFactory()->create();

        $contact->addTag(static::tagFactory()->create());
        $contact->_save();

        static::contactFactory()::assert()->count(1);
        $tag = static::tagFactory()::first();
        self::assertContains($contact->_real(), $tag->getContacts());
    }

    /**
     * @test
     */
    public function can_assert_persisted(): void
    {
        static::contactFactory()->create()->_assertPersisted();

        Assert::that(function(): void { static::contactFactory()->withoutPersisting()->create()->_assertPersisted(); })
            ->throws(AssertionFailedError::class, \sprintf('%s is not persisted.', static::contactFactory()::class()))
        ;
    }

    /**
     * @test
     */
    public function can_assert_not_persisted(): void
    {
        static::contactFactory()->withoutPersisting()->create()->_assertNotPersisted();

        Assert::that(function(): void { static::contactFactory()->create()->_assertNotPersisted(); })
            ->throws(AssertionFailedError::class, \sprintf('%s is persisted but it should not be.', static::contactFactory()::class()))
        ;
    }

    /**
     * @test
     */
    public function can_remove_and_assert_not_persisted(): void
    {
        static::contactFactory()
            ->create()
            ->_assertPersisted()
            ->_delete()
            ->_assertNotPersisted()
        ;
    }

    /**
     * @test
     */
    public function cannot_use_assert_persisted_when_entity_has_changes(): void
    {
        $contact = static::contactFactory()->create();
        $contact->setName('foo');

        $this->expectException(RefreshObjectFailed::class);
        $contact->_assertPersisted();
    }
}
