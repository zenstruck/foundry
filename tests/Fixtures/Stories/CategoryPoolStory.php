<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Stories;

use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CategoryPoolStory extends Story
{
    public function build(): void
    {
        $this->addToPool('pool-name', CategoryFactory::createMany(2));
        $this->addToPool('pool-name', CategoryFactory::new()->many(3));
        $this->addToPool('pool-name', CategoryFactory::createOne());
        $this->addToPool('pool-name', CategoryFactory::new());
        $this->addState('state-name', CategoryFactory::new(), 'pool-name');
    }
}
