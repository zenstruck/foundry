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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Foundry\Tests\Fixture\Entity\EntityWithUid;
use Zenstruck\Foundry\Tests\Integration\Persistence\PersistenceManagerTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

final class PersistenceManagerTest extends PersistenceManagerTestCase
{
    use RequiresORM;

    protected static function createObject(): object
    {
        return new EntityWithUid();
    }

    protected static function objectManager(): ObjectManager
    {
        return self::getContainer()->get(EntityManagerInterface::class); // @phpstan-ignore return.type
    }
}
