<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Stories\Fixtures;

use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

#[AsFixture(name: 'fixture-story')]
final class FixtureStoryWithNameCollision extends Story
{
    public function build(): void
    {
        GenericEntityFactory::createMany(5);
    }
}
