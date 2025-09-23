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

namespace Zenstruck\Foundry\Tests\Integration\ResetDatabase;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Tests\Fixture\FoundryTestKernel;
use Zenstruck\Foundry\Tests\Fixture\ResetDatabase\MongoResetterDecorator;
use Zenstruck\Foundry\Tests\Fixture\ResetDatabase\OrmResetterDecorator;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ResetDatabaseTest extends ResetDatabaseTestCase
{
    use ResetDatabaseTestsTrait;

    /**
     * @test
     */
    #[Test]
    public function can_extend_orm_reset_mechanism_first(): void
    {
        if (!FoundryTestKernel::hasORM()) {
            self::markTestSkipped('ORM needed.');
        }

        self::assertTrue(OrmResetterDecorator::$calledBeforeFirstTest);

        if (PersistenceManager::isOrmOnly() && FoundryTestKernel::usesDamaDoctrineTestBundle()) {
            // in this case, the resetBeforeEachTest() method is never called
            self::assertFalse(OrmResetterDecorator::$calledBeforeEachTest);
        } else {
            self::assertTrue(OrmResetterDecorator::$calledBeforeEachTest);
        }

        OrmResetterDecorator::reset();
    }

    /**
     * @test
     * @depends can_extend_orm_reset_mechanism_first
     */
    #[Test]
    #[Depends('can_extend_orm_reset_mechanism_first')]
    public function can_extend_orm_reset_mechanism_second(): void
    {
        if (!FoundryTestKernel::hasORM()) {
            self::markTestSkipped('ORM needed.');
        }

        self::assertFalse(OrmResetterDecorator::$calledBeforeFirstTest);

        if (PersistenceManager::isOrmOnly() && FoundryTestKernel::usesDamaDoctrineTestBundle()) {
            // in this case, the resetBeforeEachTest() method is never called
            self::assertFalse(OrmResetterDecorator::$calledBeforeEachTest);
        } else {
            self::assertTrue(OrmResetterDecorator::$calledBeforeEachTest);
        }
    }

    /**
     * @test
     */
    #[Test]
    public function can_extend_mongo_reset_mechanism_first(): void
    {
        if (!FoundryTestKernel::hasMongo()) {
            self::markTestSkipped('Mongo needed.');
        }

        self::assertTrue(MongoResetterDecorator::$calledBeforeEachTest);
    }
}
