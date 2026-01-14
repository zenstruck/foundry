<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Behat\Stories;

use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;

#[AsFixture(name: 'behat-categories', groups: ['behat-group'])]
final class BehatCategoriesStory extends Story
{
    public function build(): void
    {
        CategoryFactory::createMany(2, ['name' => 'Category from BehatCategoriesStory']);
    }
}
