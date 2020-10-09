<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryServiceFactory;
use Zenstruck\Foundry\Tests\FunctionalTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ModelFactoryServiceTest extends FunctionalTestCase
{
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
