<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Stories;

use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\TagFactory;

final class ODMTagStory extends Story
{
    public function build(): void
    {
        $this->addState('dev', TagFactory::new()->create(['name' => 'dev']));
    }
}
