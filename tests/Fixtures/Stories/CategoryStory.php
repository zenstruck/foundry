<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixtures\Stories;

use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CategoryStory extends Story
{
    public function build(): void
    {
        $this->addState('php', CategoryFactory::new()->create(['name' => 'php']));
        $this->addState('symfony', CategoryFactory::new()->create(['name' => 'symfony']));
    }
}
