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
use Zenstruck\Foundry\Tests\Fixture\Document\GenericDocument;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method static GenericDocument foo()
 * @method static GenericDocument bar()
 */
final class DocumentStory extends Story
{
    public function build(): void
    {
        $this->addState('foo', GenericDocumentFactory::createOne(['prop1' => 'foo']));
        $this->addState('bar', GenericDocumentFactory::createOne(['prop1' => 'bar']));
    }
}
