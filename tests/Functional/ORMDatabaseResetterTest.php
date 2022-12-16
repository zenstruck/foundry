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

namespace Zenstruck\Foundry\Tests\Functional;

use Doctrine\ORM\Tools\SchemaValidator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Test\ORMDatabaseResetter;
use Zenstruck\Foundry\Tests\Fixtures\Kernel;

final class ORMDatabaseResetterTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (\getenv('USE_DAMA_DOCTRINE_TEST_BUNDLE')) {
            self::markTestSkipped('The database should not be reset if dama/doctrine-test-bundle is enabled.');
        }

        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm is not enabled.');
        }
    }

    /**
     * @test
     * @dataProvider databaseResetterProvider
     */
    public function it_resets_database_correctly(string $resetMode): void
    {
        $application = new Application(self::bootKernel(['ormResetMode' => $resetMode]));
        $application->setAutoExit(false);

        $container = self::$kernel->getContainer();

        $resetter = new ORMDatabaseResetter($application, $container->get('doctrine'), [], [], $resetMode);

        $resetter->resetDatabase();

        $validator = new SchemaValidator($container->get('doctrine')->getManager());
        self::assertEmpty($validator->validateMapping());
        self::assertTrue($validator->schemaInSyncWithMetadata());
    }

    public function databaseResetterProvider(): iterable
    {
        yield [ORMDatabaseResetter::RESET_MODE_SCHEMA];
        yield [ORMDatabaseResetter::RESET_MODE_MIGRATE];
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        return Kernel::create(true, $options['ormResetMode']);
    }
}
