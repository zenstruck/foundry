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

namespace Zenstruck\Foundry\Tests\Integration\Migration;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Entity\GlobalEntity;
use Zenstruck\Foundry\Tests\Fixture\EntityInAnotherSchema\Article;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;
use Zenstruck\Foundry\Tests\Fixture\MigrationTests\TestMigrationKernel;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\persist;
use function Zenstruck\Foundry\Persistence\repository;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ResetDatabaseWithMigrationTest extends KernelTestCase
{
    use Factories;
    use RequiresORM;
    use ResetDatabase;

    /**
     * @test
     */
    public function it_generates_valid_schema(): void
    {
        $application = new Application(self::bootKernel());
        $application->setAutoExit(false);

        $exit = $application->run(new ArrayInput(['command' => 'doctrine:schema:validate', '-v' => true]), new BufferedOutput());

        self::assertSame(0, $exit);
    }

    /**
     * @test
     */
    public function it_can_store_object(): void
    {
        ContactFactory::assert()->count(0);

        ContactFactory::createOne();

        ContactFactory::assert()->count(1);
    }

    /**
     * @test
     * @depends it_can_store_object
     */
    public function it_starts_from_fresh_db(): void
    {
        ContactFactory::assert()->count(0);
    }

    /**
     * @test
     */
    public function global_objects_are_created(): void
    {
        repository(GlobalEntity::class)->assert()->count(2);
    }

    /**
     * @test
     */
    public function can_create_object_in_another_schema(): void
    {
        if (!\str_starts_with(\getenv('DATABASE_URL') ?: '', 'postgresql')) {
            self::markTestSkipped('PostgreSQL needed.');
        }

        persist(Article::class, ['title' => 'Hello World!']);
        repository(Article::class)->assert()->count(1);
    }

    protected static function getKernelClass(): string
    {
        return TestMigrationKernel::class;
    }
}
