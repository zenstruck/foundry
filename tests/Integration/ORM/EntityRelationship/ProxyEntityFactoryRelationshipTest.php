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
use Doctrine\Persistence\Proxy as DoctrineProxy;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Assert;
use Zenstruck\Foundry\Persistence\Exception\RefreshObjectFailed;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Tests\Fixture\DoctrineCascadeRelationship\UsingRelationships;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\ProxyAddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\ProxyCategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ProxyContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Tag\ProxyTagFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit ^11.4
 */
#[RequiresPhpunit('^11.4')]
final class ProxyEntityFactoryRelationshipTest extends EntityFactoryRelationshipTestCase
{
    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[UsingRelationships(Contact::class, ['category'])]
    public function doctrine_proxies_are_converted_to_foundry_proxies(): void
    {
        static::contactFactory()->create(['category' => static::categoryFactory()]);

        // clear the em so nothing is tracked
        self::getContainer()->get(EntityManagerInterface::class)->clear(); // @phpstan-ignore method.notFound

        // load a random Contact which causes the em to track a "doctrine proxy" for category
        static::contactFactory()::random();

        // load a random Category which should be a "doctrine proxy"
        $category = static::categoryFactory()::random();

        // ensure the category is a "doctrine proxy" and a Category
        $this->assertInstanceOf(Proxy::class, $category);
        $this->assertInstanceOf(DoctrineProxy::class, $category->_real());
        $this->assertInstanceOf(static::categoryFactory()::class(), $category);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[UsingRelationships(Contact::class, ['category'])]
    public function it_can_add_proxy_to_many_to_one(): void
    {
        $contact = static::contactFactory()->create();

        $contact->setCategory($category = static::categoryFactory()->create());
        $contact->_save();

        static::contactFactory()::assert()->count(1);
        static::contactFactory()::assert()->exists(['category' => $category]);
    }

    /** @test */
    #[Test]
    #[DataProvider('provideCascadeRelationshipsCombinations')]
    #[UsingRelationships(Contact::class, ['tags'])]
    public function it_can_add_proxy_to_one_to_many(): void
    {
        $contact = static::contactFactory()->create();

        $contact->addTag(static::tagFactory()->create());
        $contact->_save();

        static::contactFactory()::assert()->count(1);
        $tag = static::tagFactory()::first();
        self::assertContains($contact->_real(), $tag->getContacts());
    }

    /** @test */
    #[Test]
    public function can_assert_persisted(): void
    {
        static::contactFactory()->create()->_assertPersisted();

        Assert::that(function(): void { static::contactFactory()->withoutPersisting()->create()->_assertPersisted(); })
            ->throws(AssertionFailedError::class, \sprintf('%s is not persisted.', static::contactFactory()::class()))
        ;
    }

    /** @test */
    #[Test]
    public function can_assert_not_persisted(): void
    {
        static::contactFactory()->withoutPersisting()->create()->_assertNotPersisted();

        Assert::that(function(): void { static::contactFactory()->create()->_assertNotPersisted(); })
            ->throws(AssertionFailedError::class, \sprintf('%s is persisted but it should not be.', static::contactFactory()::class()))
        ;
    }

    /** @test */
    #[Test]
    public function can_remove_and_assert_not_persisted(): void
    {
        static::contactFactory()
            ->create()
            ->_assertPersisted()
            ->_delete()
            ->_assertNotPersisted()
        ;
    }

    /** @test */
    #[Test]
    public function can_use_assert_persisted_when_entity_has_changes(): void
    {
        $contact = static::contactFactory()->create();
        $contact->setName('foo');

        $contact->_assertPersisted();
    }

    protected static function contactFactory(): ProxyContactFactory
    {
        return ProxyContactFactory::new();
    }

    protected static function categoryFactory(): ProxyCategoryFactory
    {
        return ProxyCategoryFactory::new();
    }

    protected static function tagFactory(): ProxyTagFactory
    {
        return ProxyTagFactory::new();
    }

    protected static function addressFactory(): ProxyAddressFactory
    {
        return ProxyAddressFactory::new();
    }
}
