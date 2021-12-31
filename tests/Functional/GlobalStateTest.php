<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixtures\Factories\TagFactory;
use Zenstruck\Foundry\Tests\Fixtures\Stories\TagStory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class GlobalStateTest extends KernelTestCase
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
    public function tag_story_is_added_as_global_state(): void
    {
        TagFactory::repository()->assert()->count(2);
        TagFactory::repository()->assert()->exists(['name' => 'dev']);
        TagFactory::repository()->assert()->exists(['name' => 'design']);
    }

    /**
     * @test
     */
    public function ensure_global_story_is_not_loaded_again(): void
    {
        TagStory::load();
        TagStory::load();

        TagFactory::repository()->assert()->count(2);
    }
}
