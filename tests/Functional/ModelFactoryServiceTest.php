<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryServiceFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ModelFactoryServiceTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    /**
     * @before
     */
    public function skipIfNotUsingFoundryBundle(): void
    {
        if (!\getenv('USE_FOUNDRY_BUNDLE')) {
            $this->markTestSkipped('ZenstruckFoundryBundle not enabled.');
        }
    }

    /**
     * @test
     */
    public function can_create_service_factory(): void
    {
        $factory = CategoryServiceFactory::new();

        $this->assertSame('From Service', $factory->create()->getName());
        $this->assertSame('From Factory Create', $factory->create(['name' => 'From Factory Create'])->getName());
    }

    /**
     * @test
     */
    public function service_factories_are_not_the_same_object(): void
    {
        $this->assertNotSame(CategoryServiceFactory::new(), CategoryServiceFactory::new());
    }
}
