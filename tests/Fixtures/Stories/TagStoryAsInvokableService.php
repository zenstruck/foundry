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

use Zenstruck\Foundry\Tests\Fixtures\Factories\TagFactory;

final class TagStoryAsInvokableService
{
    public function __invoke()
    {
        TagFactory::new()->create(['name' => 'design']);
    }
}
