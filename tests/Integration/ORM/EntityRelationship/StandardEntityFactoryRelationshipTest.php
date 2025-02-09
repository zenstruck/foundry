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

use PHPUnit\Framework\Attributes\RequiresPhpunit;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\AddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Tag\TagFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[RequiresPhpunit('>=11.4')]
final class StandardEntityFactoryRelationshipTest extends EntityFactoryRelationshipTestCase
{
    protected static function contactFactory(): ContactFactory
    {
        return ContactFactory::new();
    }

    protected static function categoryFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    protected static function tagFactory(): TagFactory
    {
        return TagFactory::new();
    }

    protected static function addressFactory(): AddressFactory
    {
        return AddressFactory::new();
    }
}
