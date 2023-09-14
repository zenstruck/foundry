<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Stories;

use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;

final class StoryWhichReadsItsOwnPool extends Story
{
    public function build(): void
    {
        $this->addState('php', CategoryFactory::new()->create(['name' => 'php']), 'some-pool');
        $this->addState('symfony', CategoryFactory::new()->create(['name' => 'symfony']), 'some-pool');

        // story can access its own state
        $this->addState('php2', self::getState('php'), 'some-pool');

        // story can access its own pool
        $this->addState('random-from-own-pool', self::getRandom('some-pool'));
    }
}
