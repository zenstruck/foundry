<?php

declare(strict_types=1);

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
    public function it_generates_a_valid_schema(): void
    {
        $kernel = static::bootKernel();
        $validator = new SchemaValidator($kernel->getContainer()->get('doctrine')->getManager());
        self::assertEmpty($validator->validateMapping());
        self::assertTrue($validator->schemaInSyncWithMetadata());
    }

    /**
     * @test
     */
    public function it_can_use_schema_reset_with_migration(): void
    {
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

        return Kernel::create(true, ORMDatabaseResetter::RESET_MODE_MIGRATE);
    }
}
