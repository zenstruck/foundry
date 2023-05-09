<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\BaseFactory;
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
     * @return class-string<BaseFactory>
     */
    abstract protected function getTagFactoryClass(): string;

    /**
     * @return class-string<Story>
     */
    abstract protected function getTagStoryClass(): string;
}
