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
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\RelationshipWithGlobalEntity\StandardRelationshipWithGlobalEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\StandardAddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\StandardCategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\StandardContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Tag\StandardTagFactory;

use function Zenstruck\Foundry\Persistence\persistent_factory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
class StandardEntityFactoryRelationshipTest extends EntityFactoryRelationshipTestCase
{
    protected function contactFactory(): PersistentObjectFactory
    {
        return StandardContactFactory::new(); // @phpstan-ignore-line
    }

    protected function categoryFactory(): PersistentObjectFactory
    {
        return StandardCategoryFactory::new(); // @phpstan-ignore-line
    }

    protected function tagFactory(): PersistentObjectFactory
    {
        return StandardTagFactory::new(); // @phpstan-ignore-line
    }

    protected function addressFactory(): PersistentObjectFactory
    {
        return StandardAddressFactory::new(); // @phpstan-ignore-line
    }
}
