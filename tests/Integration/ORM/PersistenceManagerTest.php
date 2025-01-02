<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Foundry\Tests\Integration\Persistence\PersistenceManagerTestCase;
use Zenstruck\Foundry\Tests\Fixture\Entity\EntityWithUid;
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
