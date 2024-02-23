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
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class DocumentPoolStory extends Story
{
    public function build(): void
    {
        $this->addState('foo', GenericDocumentFactory::createOne(['prop1' => 'foo']), self::class);
        $this->addToPool(self::class, GenericDocumentFactory::createMany(2));

        // story can access its own state
        $this->addState('foo2', self::getState('foo'), self::class);

        // story can access its own pool
        $this->addState('random-from-own-pool', self::getRandom(self::class));
    }
}
