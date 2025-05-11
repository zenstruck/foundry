<?php

namespace Zenstruck\Foundry\Tests\Fixture\Stories\Fixtures;

use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

#[AsFixture(name: 'fixture-using-another-fixture', groups: ['fixture-using-another-fixture-group'])]
final class FixtureUsingAnotherFixtureStory extends Story
{
    public function build(): void
    {
        GenericEntityFactory::createOne(['prop1' => 'fixture-using-another-fixture']);

        FixtureStory::load();
    }
}
