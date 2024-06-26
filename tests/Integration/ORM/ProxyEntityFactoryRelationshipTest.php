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
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\ProxyAddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\ProxyCategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ProxyContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Tag\ProxyTagFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProxyEntityFactoryRelationshipTest extends ProxyEntityFactoryRelationshipTestCase
{
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
