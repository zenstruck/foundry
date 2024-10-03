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
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @method static GenericEntity foo()
 * @method static GenericEntity bar()
 * @method static int           int()
 * @method static float         float()
 * @method static string        string()
 * @method static bool          bool()
 * @method static array         array()
 * @method static null          null()
 */
final class EntityStory extends Story
{
    public function build(): void
    {
        $this->addState('foo', GenericEntityFactory::createOne(['prop1' => 'foo']), 'pool');
        $this->addState('bar', GenericEntityFactory::createOne(['prop1' => 'bar']), 'pool');
        $this->addState('int', 12);
        $this->addState('float', 12.12);
        $this->addState('string', 'dummyString');
        $this->addState('bool', true);
        $this->addState('array', [12, 'dummyString', [true, 12.12]]);
        $this->addState('null', null);
    }
}
