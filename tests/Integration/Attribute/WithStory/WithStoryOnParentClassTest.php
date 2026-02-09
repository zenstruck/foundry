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
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Stories\EntityPoolStory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[WithStory(EntityPoolStory::class)]
#[RequiresEnvironmentVariable('DATABASE_URL')]
final class WithStoryOnParentClassTest extends ParentClassWithStoryAttributeTestCase
{
    #[Test]
    public function can_use_story_in_attribute_from_parent_class(): void
    {
        GenericEntityFactory::assert()->count(5);
    }
}
