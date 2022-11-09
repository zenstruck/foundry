<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Stories;

use Zenstruck\Foundry\Tests\Fixtures\Factories\TagFactory;

final class TagStoryAsInvokableService
{
    public function __invoke()
    {
        TagFactory::new()->create(['name' => 'design']);
    }
}
