<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Stories;

use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixtures\Factories\TagFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TagStory extends Story
{
    public function build(): void
    {
        $this->addState('dev', TagFactory::new()->create(['name' => 'dev']));
        $this->addState('design', TagFactory::new()->create(['name' => 'design']));
    }
}
