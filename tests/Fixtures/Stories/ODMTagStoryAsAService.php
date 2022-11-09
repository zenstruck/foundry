<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixtures\Stories;

use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\TagFactory;

final class ODMTagStoryAsAService extends Story
{
    public function build(): void
    {
        $this->addState('design', TagFactory::new()->create(['name' => 'design']));
    }
}
