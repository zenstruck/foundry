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
 * @method static int             int()
 * @method static float           float()
 * @method static string          string()
 * @method static bool            bool()
 * @method static array           array()
 * @method static null            null()
 */
final class DocumentStory extends Story
{
    public function build(): void
    {
        $this->addState('foo', GenericDocumentFactory::createOne(['prop1' => 'foo']));
        $this->addState('bar', GenericDocumentFactory::createOne(['prop1' => 'bar']));
        $this->addState('int', 12);
        $this->addState('float', 12.12);
        $this->addState('string', 'dummyString');
        $this->addState('bool', true);
        $this->addState('array', [12, 'dummyString', [true, 12.12]]);
        $this->addState('null', null);
    }
}
