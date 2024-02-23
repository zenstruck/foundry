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

use Doctrine\Common\Proxy\Proxy as DoctrineProxy;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category\StandardCategory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\ProxyAddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\ProxyCategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ProxyContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Tag\ProxyTagFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EntityProxyFactoryRelationshipTest extends EntityFactoryRelationshipTest
{
    /**
     * @see https://github.com/zenstruck/foundry/issues/42
     *
     * @test
     */
    public function doctrine_proxies_are_converted_to_foundry_proxies(): void
    {
        $this->contactFactory()->create(['category' => $this->categoryFactory()]);

        // clear the em so nothing is tracked
        self::getContainer()->get(EntityManagerInterface::class)->clear(); // @phpstan-ignore-line

        // load a random Contact which causes the em to track a "doctrine proxy" for category
        $this->contactFactory()::random();

        // load a random Category which should be a "doctrine proxy"
        $category = $this->categoryFactory()::random();

        // ensure the category is a "doctrine proxy" and a Category
        $this->assertInstanceOf(Proxy::class, $category);
        $this->assertInstanceOf(DoctrineProxy::class, $category);
        $this->assertInstanceOf(StandardCategory::class, $category);
    }

    protected function contactFactory(): PersistentObjectFactory
    {
        return ProxyContactFactory::new(); // @phpstan-ignore-line
    }

    protected function categoryFactory(): PersistentObjectFactory
    {
        return ProxyCategoryFactory::new(); // @phpstan-ignore-line
    }

    protected function tagFactory(): PersistentObjectFactory
    {
        return ProxyTagFactory::new(); // @phpstan-ignore-line
    }

    protected function addressFactory(): PersistentObjectFactory
    {
        return ProxyAddressFactory::new(); // @phpstan-ignore-line
    }
}
