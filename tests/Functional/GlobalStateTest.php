<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\CategoryStory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class GlobalStateTest extends KernelTestCase
{
    use ResetDatabase, Factories;

    /**
     * @test
     */
    public function category_story_is_added_as_global_state(): void
    {
        CategoryFactory::repository()->assertCount(2);
        CategoryFactory::repository()->assertExists(['name' => 'php']);
        CategoryFactory::repository()->assertExists(['name' => 'symfony']);
    }

    /**
     * @test
     */
    public function ensure_global_story_is_not_loaded_again(): void
    {
        CategoryStory::load();
        CategoryStory::load();

        CategoryFactory::repository()->assertCount(2);
    }
}
