<?php

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
        $this->add('postA', PostFactory::new()->create([
            'title' => 'Post A',
            'category' => CategoryStory::php(),
        ]));

        $this->add('postB', PostFactory::new()->create([
            'title' => 'Post B',
            'category' => CategoryStory::php(),
        ])->object());

        $this->add('postC', PostFactory::new([
            'title' => 'Post C',
            'category' => CategoryStory::symfony(),
        ]));

        PostFactory::new()->create([
            'title' => 'Post D',
            'category' => CategoryStory::php(),
        ]);
    }
}
