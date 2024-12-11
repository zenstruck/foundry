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
