<?php

namespace Zenstruck\Foundry\Tests\Fixture\Stories\Fixtures;

use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

#[AsFixture(name: 'fixture-story', groups: ['single-fixture-in-group', 'multiple-fixtures-in-group', 'fixture-using-another-fixture-group'])]
final class FixtureStory extends Story
{
    public function build(): void
    {
        GenericEntityFactory::createOne(['prop1' => 'fixture-story']);
    }
}
