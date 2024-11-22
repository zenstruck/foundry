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
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ChildContactFactory;

/**
 * tests behavior with inheritance.
 */
class PolymorphicEntityFactoryRelationshipTest extends StandardEntityFactoryRelationshipTest
{
    protected static function contactFactory(): PersistentObjectFactory
    {
        return ChildContactFactory::new(); // @phpstan-ignore return.type
    }
}
