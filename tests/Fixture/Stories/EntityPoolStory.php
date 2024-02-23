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
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EntityPoolStory extends Story
{
    public function build(): void
    {
        $this->addState('foo', GenericEntityFactory::createOne(['prop1' => 'foo']), self::class);
        $this->addToPool(self::class, GenericEntityFactory::createMany(2));

        // story can access its own state
        $this->addState('foo2', self::getState('foo'), self::class);

        // story can access its own pool
        $this->addState('random-from-own-pool', self::getRandom(self::class));
    }
}
