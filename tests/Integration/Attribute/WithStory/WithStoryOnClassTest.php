<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Attribute\WithStory;

use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityPoolStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityStory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[WithStory(EntityStory::class)]
#[ResetDatabase]
#[RequiresEnvironmentVariable('DATABASE_URL')]
final class WithStoryOnClassTest extends KernelTestCase
{
    #[Test]
    public function can_use_story_in_attribute(): void
    {
        GenericEntityFactory::assert()->count(2);

        // ensure state is accessible
        $this->assertSame('foo', EntityStory::get('foo')->getProp1());
    }

    #[Test]
    #[WithStory(EntityStory::class)]
    public function can_use_story_in_attribute_multiple_times(): void
    {
        GenericEntityFactory::assert()->count(2);
    }

    #[Test]
    #[WithStory(EntityPoolStory::class)]
    public function can_use_another_story_at_level_class(): void
    {
        GenericEntityFactory::assert()->count(5);
    }
}
