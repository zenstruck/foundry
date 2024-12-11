<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\ORM\EntityRelationship;

use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\StandardAddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\StandardCategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ChildContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Tag\StandardTagFactory;

/**
 * tests behavior with inheritance.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class PolymorphicEntityFactoryRelationshipTest extends EntityFactoryRelationshipTestCase
{
    protected static function contactFactory(): ChildContactFactory
    {
        return ChildContactFactory::new();
    }

    protected static function categoryFactory(): StandardCategoryFactory
    {
        return StandardCategoryFactory::new();
    }

    protected static function tagFactory(): StandardTagFactory
    {
        return StandardTagFactory::new();
    }

    protected static function addressFactory(): StandardAddressFactory
    {
        return StandardAddressFactory::new();
    }
}
