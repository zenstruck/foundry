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
use Zenstruck\Foundry\Tests\Fixtures\Factories\ODM\TagFactory;

final class ODMTagStory extends Story
{
    public function build(): void
    {
        $this->addState('dev', TagFactory::new()->create(['name' => 'dev']));
    }
}
