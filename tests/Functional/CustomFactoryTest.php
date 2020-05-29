<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\FunctionalTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CustomFactoryTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function can_find_or_create(): void
    {
        CategoryFactory::repository()->assertCount(0);
        CategoryFactory::findOrCreate(['name' => 'php']);
        CategoryFactory::repository()->assertCount(1);
        CategoryFactory::findOrCreate(['name' => 'php']);
        CategoryFactory::repository()->assertCount(1);
    }
}
