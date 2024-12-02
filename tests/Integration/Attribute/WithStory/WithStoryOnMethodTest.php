<?php

declare(strict_types=1);

namespace Integration\Attribute\WithStory;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityPoolStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityStory;

final class WithStoryOnMethodTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    /**
     * @test
     */
    #[WithStory(EntityStory::class)]
    public function can_use_story_in_attribute(): void
    {
        GenericEntityFactory::assert()->count(2);

        // ensure state is accessible
        $this->assertSame('foo', EntityStory::get('foo')->getProp1());
    }

    /**
     * @test
     */
    #[WithStory(EntityStory::class)]
    #[WithStory(EntityPoolStory::class)]
    public function can_use_multiple_story_in_attribute(): void
    {
        GenericEntityFactory::assert()->count(5);
    }
}
