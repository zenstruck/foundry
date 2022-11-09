<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class GlobalStateTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    protected function setUp(): void
    {
        if (!\getenv('USE_FOUNDRY_BUNDLE')) {
            $this->markTestSkipped('ZenstruckFoundryBundle not enabled.');
        }

        parent::setUp();
    }

    /**
     * @test
     */
    public function tag_story_is_added_as_global_state(): void
    {
        $tagFactoryClass = $this->getTagFactoryClass();
        $tagFactoryClass::repository()->assert()->count(2);
        $tagFactoryClass::repository()->assert()->exists(['name' => 'dev']);
        $tagFactoryClass::repository()->assert()->exists(['name' => 'design']);
    }

    /**
     * @test
     */
    public function ensure_global_story_is_not_loaded_again(): void
    {
        $tagStoryClass = $this->getTagStoryClass();
        $tagStoryClass::load();
        $tagStoryClass::load();

        $tagFactoryClass = $this->getTagFactoryClass();
        $tagFactoryClass::repository()->assert()->count(2);
    }

    /**
     * @return class-string<Factory>
     */
    abstract protected function getTagFactoryClass(): string;

    /**
     * @return class-string<Story>
     */
    abstract protected function getTagStoryClass(): string;
}
