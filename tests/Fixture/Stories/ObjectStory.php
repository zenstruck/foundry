<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Stories;

use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;
use Zenstruck\Foundry\Tests\Fixture\Object1;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @method static Object1 foo()
 */
final class ObjectStory extends Story
{
    public function build(): void
    {
        $this->addState('foo', Object1Factory::createOne(), 'pool');
    }
}
