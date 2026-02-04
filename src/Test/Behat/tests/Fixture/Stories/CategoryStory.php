<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Tests\Fixture\Stories;

use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;

#[AsFixture(name: 'behat-category', groups: ['behat-stories'])]
final class CategoryStory extends Story
{
    public function build(): void
    {
        $this->addState(
            'category fixture',
            CategoryFactory::createOne()
        );
    }
}
