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
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @method static GenericEntity foo()
 */
final class PersistenceDisabledStory extends Story
{
    public function build(): void
    {
        $this->addState('foo', GenericEntityFactory::new()->withoutPersisting()->create(), 'pool');
    }
}
