<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryServiceFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ModelFactoryServiceTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    protected function setUp(): void
    {
        if (false === \getenv('DATABASE_URL')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }
    }

    /**
     * @test
     */
    public function can_create_service_factory(): void
    {
        if (!\getenv('USE_FOUNDRY_BUNDLE')) {
            $this->markTestSkipped('ZenstruckFoundryBundle not enabled.');
        }

        $factory = CategoryServiceFactory::new();

        $this->assertSame('From Service', $factory->create()->getName());
        $this->assertSame('From Factory Create', $factory->create(['name' => 'From Factory Create'])->getName());
    }

    /**
     * @test
     */
    public function service_factories_are_not_the_same_object(): void
    {
        if (!\getenv('USE_FOUNDRY_BUNDLE')) {
            $this->markTestSkipped('ZenstruckFoundryBundle not enabled.');
        }

        $this->assertNotSame(CategoryServiceFactory::new(), CategoryServiceFactory::new());
    }

    /**
     * @test
     */
    public function service_factories_cannot_be_used_without_bundle(): void
    {
        if (\getenv('USE_FOUNDRY_BUNDLE')) {
            $this->markTestSkipped('ZenstruckFoundryBundle enabled.');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Model Factories with dependencies (Model Factory services) cannot be used without the foundry bundle.');

        CategoryServiceFactory::new();
    }

    /**
     * @test
     */
    public function cannot_create_service_factories_without_foundry_booted(): void
    {
        Factory::shutdown();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Model Factories with dependencies (Model Factory services) cannot be created before foundry is booted.');

        CategoryServiceFactory::new();
    }
}
