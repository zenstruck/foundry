<?php

namespace Zenstruck\Foundry\Tests\Fixture\Stories\Fixtures;

use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

#[AsFixture(name: 'story-with-name-collision', groups: ['fixture-story'])]
final class FixtureStoryWithGroupNameCollision extends Story
{
    public function build(): void
    {
        GenericEntityFactory::createMany(5);
    }
}
