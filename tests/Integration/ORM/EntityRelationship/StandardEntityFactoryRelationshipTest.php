<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\ORM\EntityRelationship;

use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\StandardAddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\StandardCategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\StandardContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Tag\StandardTagFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class StandardEntityFactoryRelationshipTest extends EntityFactoryRelationshipTestCase
{
    protected static function contactFactory(): StandardContactFactory
    {
        return StandardContactFactory::new();
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
