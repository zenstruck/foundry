<?php

namespace Zenstruck\Foundry\Tests\Fixture\Stories;

use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

#[AsFixture(name: 'fixture-story')]
final class FixtureStory extends Story
{
    public function build(): void
    {
        GenericEntityFactory::createOne();
    }
}
