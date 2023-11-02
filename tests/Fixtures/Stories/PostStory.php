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
use Zenstruck\Foundry\Tests\Fixtures\Factories\PostFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PostStory extends Story
{
    public function build(): void
    {
        $this->addState('postA', PostFactory::new()->create([
            'title' => 'Post A',
            'category' => CategoryStory::php(),
        ]));

        $this->addState('postB', PostFactory::new()->create([
            'title' => 'Post B',
            'category' => CategoryStory::php(),
        ])->_real());

        $this->addState('postC', PostFactory::new([
            'title' => 'Post C',
            'category' => CategoryStory::symfony(),
        ]));

        PostFactory::new()->create([
            'title' => 'Post D',
            'category' => CategoryStory::php(),
        ]);
    }
}
