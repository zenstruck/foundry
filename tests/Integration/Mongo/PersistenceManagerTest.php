<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Mongo;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use Zenstruck\Foundry\Tests\Fixture\Document\DocumentWithUid;
use Zenstruck\Foundry\Tests\Integration\Persistence\PersistenceManagerTestCase;

#[RequiresEnvironmentVariable('MONGO_URL')]
final class PersistenceManagerTest extends PersistenceManagerTestCase
{
    protected static function createObject(): object
    {
        return new DocumentWithUid();
    }

    protected static function objectManager(): ObjectManager
    {
        return self::getContainer()->get(DocumentManager::class); // @phpstan-ignore return.type
    }
}
