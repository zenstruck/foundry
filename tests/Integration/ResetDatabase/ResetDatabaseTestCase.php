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
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase as ResetDatabaseTrait;
use Zenstruck\Foundry\Tests\Fixture\EntityInAnotherSchema\Article;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\FoundryTestKernel;
use Zenstruck\Foundry\Tests\Fixture\ResetDatabase\MongoResetterDecorator;
use Zenstruck\Foundry\Tests\Fixture\ResetDatabase\OrmResetterDecorator;
use Zenstruck\Foundry\Tests\Fixture\ResetDatabase\ResetDatabaseTestKernel;
use function Zenstruck\Foundry\Persistence\persist;
use function Zenstruck\Foundry\Persistence\repository;

abstract class ResetDatabaseTestCase extends KernelTestCase
{
    #[Test]
    public function it_generates_valid_schema(): void
    {
        $application = new Application(self::bootKernel());
        $application->setAutoExit(false);

        $exit = $application->run(
            new ArrayInput(['command' => 'doctrine:schema:validate', '-v' => true]),
            $output = new BufferedOutput()
        );

        if (FoundryTestKernel::usesMigrations()) {
            // The command actually fails, because of a bug in doctrine ORM 3!
            // https://github.com/doctrine/migrations/issues/1406
            self::assertSame(2, $exit, \sprintf('Schema is not valid: %s', $commandOutput = $output->fetch()));
            self::assertStringContainsString('1 schema diff(s) detected', $commandOutput);
            self::assertStringContainsString('DROP TABLE doctrine_migration_versions', $commandOutput);
        } elseif (ResetDatabaseTestKernel::usesSqlite()) {
            // Don't know why sqlite always generate those requests with the derived entity ¯\_(ツ)_/¯
            self::assertSame(2, $exit, \sprintf('Schema is not valid: %s', $commandOutput = $output->fetch()));
            self::assertStringContainsString('5 schema diff(s) detected', $commandOutput);
            self::assertStringContainsString('CREATE TEMPORARY TABLE __temp__edge_case_derived_id_inverse_side', $commandOutput);
            self::assertStringContainsString('DROP TABLE edge_case_derived_id_inverse_side;', $commandOutput);
            self::assertStringContainsString('CREATE TABLE edge_case_derived_id_inverse_side', $commandOutput);
            self::assertStringContainsString('INSERT INTO edge_case_derived_id_inverse_side', $commandOutput);
            self::assertStringContainsString('DROP TABLE __temp__edge_case_derived_id_inverse_side;', $commandOutput);
        } else {
            self::assertSame(0, $exit, \sprintf('Schema is not valid: %s', $output->fetch()));
        }
    }

    #[Test]
    public function it_can_store_object(): void
    {
        if (FoundryTestKernel::hasORM()) {
            GenericEntityFactory::assert()->count(0);
            GenericEntityFactory::createOne();
            GenericEntityFactory::assert()->count(1);
        }

        if (FoundryTestKernel::hasMongo()) {
            GenericDocumentFactory::assert()->count(0);
            GenericDocumentFactory::createOne();
            GenericDocumentFactory::assert()->count(1);
        }
    }

    #[Test]
    #[Depends('it_can_store_object')]
    public function it_still_starts_from_fresh_db(): void
    {
        if (FoundryTestKernel::hasORM()) {
            GenericEntityFactory::assert()->count(0);
        }

        if (FoundryTestKernel::hasMongo()) {
            GenericDocumentFactory::assert()->count(0);
        }
    }

    #[Test]
    public function can_create_object_in_another_schema(): void
    {
        if (!\str_starts_with(\getenv('DATABASE_URL') ?: '', 'postgresql')) {
            self::markTestSkipped('PostgreSQL needed.');
        }

        persist(Article::class, ['title' => 'Hello World!']);
        repository(Article::class)->assert()->count(1);
    }

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

    #[Test]
    public function can_extend_mongo_reset_mechanism_first(): void
    {
        if (!FoundryTestKernel::hasMongo()) {
            self::markTestSkipped('Mongo needed.');
        }

        self::assertTrue(MongoResetterDecorator::$calledBeforeEachTest);
    }

    protected static function getKernelClass(): string
    {
        return ResetDatabaseTestKernel::class;
    }
}
