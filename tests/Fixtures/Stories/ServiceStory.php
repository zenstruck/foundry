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
use Zenstruck\Foundry\Tests\Fixtures\Service;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ServiceStory extends Story
{
    public function __construct(private Service $service)
    {
    }

    public function build(): void
    {
        $this->addState('post', PostFactory::new()->create(['title' => $this->service->name]));
    }
}
