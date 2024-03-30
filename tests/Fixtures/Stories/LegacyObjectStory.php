<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Stories;

use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixtures\Factories\LegacyObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 *
 * @method static SomeObject simpleObject()
 */
class LegacyObjectStory extends Story
{
    public function build(): void
    {
        $this->addState('simpleObject', LegacyObjectFactory::createOne());
    }
}
