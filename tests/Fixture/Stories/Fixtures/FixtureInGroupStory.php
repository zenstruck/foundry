<?php

namespace Zenstruck\Foundry\Tests\Fixture\Stories\Fixtures;

use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

#[AsFixture(name: 'fixture-story-for-group', groups: ['multiple-fixtures-in-group'])]
final class FixtureInGroupStory extends Story
{
    public function build(): void
    {
        GenericEntityFactory::createOne(['prop1' => 'fixture-story-for-group']);
    }
}
