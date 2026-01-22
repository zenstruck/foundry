<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Attribute\WithStory;

use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityPoolStory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityStory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.0
 */
#[RequiresPhpunit('>=11.0')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[WithStory(EntityStory::class)]
#[ResetDatabase]
final class WithStoryOnClassTest extends KernelTestCase
{
    use RequiresORM;

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
