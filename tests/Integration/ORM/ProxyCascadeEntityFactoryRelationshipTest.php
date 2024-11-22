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
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\ProxyCascadeAddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\ProxyCascadeCategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ProxyCascadeContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Tag\ProxyCascadeTagFactory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ProxyCascadeEntityFactoryRelationshipTest extends ProxyEntityFactoryRelationshipTestCase
{
    protected static function contactFactory(): PersistentObjectFactory
    {
        return ProxyCascadeContactFactory::new(); // @phpstan-ignore return.type
    }

    protected static function categoryFactory(): PersistentObjectFactory
    {
        return ProxyCascadeCategoryFactory::new(); // @phpstan-ignore return.type
    }

    protected static function tagFactory(): PersistentObjectFactory
    {
        return ProxyCascadeTagFactory::new(); // @phpstan-ignore return.type
    }

    protected static function addressFactory(): PersistentObjectFactory
    {
        return ProxyCascadeAddressFactory::new(); // @phpstan-ignore return.type
    }
}
