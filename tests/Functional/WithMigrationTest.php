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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Test\ORMDatabaseResetter;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;
use Zenstruck\Foundry\Tests\Fixtures\Kernel;

final class WithMigrationTest extends KernelTestCase
{
    use ResetDatabase;

    /**
     * @test
     */
    public function it_can_use_schema_reset_with_migration(): void
    {
        $kernel = static::bootKernel();

        // assert schema is valid
        $validator = new SchemaValidator($kernel->getContainer()->get('doctrine')->getManager());
        self::assertEmpty(
            $validator->validateMapping(),
            \implode("\n", \array_map(static fn($s): string => \implode("\n", $s), $validator->validateMapping()))
        );
        self::assertTrue($validator->schemaInSyncWithMetadata());

        // assert it can be used
        PostFactory::createOne();
        PostFactory::assert()->count(1);
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        // ResetDatabase trait uses @beforeClass annotation which would always be called before setUpBeforeClass()
        // but it also calls "static::createKernel()" which we can use to skip test if USE_ORM is false.
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }

        if (!str_starts_with(\getenv('DATABASE_URL'), 'postgres')) {
            self::markTestSkipped('Can only test migrations with postgresql.');
        }

        return Kernel::create(true, ORMDatabaseResetter::RESET_MODE_MIGRATE);
    }
}
