<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\Attribute\WithStory;

use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityPoolStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityStory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit 11
 */
#[RequiresPhpunit('11')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[WithStory(EntityStory::class)]
final class WithStoryOnClassTest extends KernelTestCase
{
    use Factories, ResetDatabase, RequiresORM;

    /**
     * @test
     */
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
    public function can_use_story_in_attribute_multiple_times(): void
    {
        GenericEntityFactory::assert()->count(2);
    }

    /**
     * @test
     */
    #[WithStory(EntityPoolStory::class)]
    public function can_use_another_story_at_level_class(): void
    {
        GenericEntityFactory::assert()->count(5);
    }
}
