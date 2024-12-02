<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Integration\Attribute\WithStory;

use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityPoolStory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit 11
 */
#[RequiresPhpunit('11')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[WithStory(EntityPoolStory::class)]
final class WithStoryOnParentClassTest extends ParentClassWithStoryAttributeTestCase
{
    /**
     * @test
     */
    public function can_use_story_in_attribute_from_parent_class(): void
    {
        GenericEntityFactory::assert()->count(5);
    }
}
