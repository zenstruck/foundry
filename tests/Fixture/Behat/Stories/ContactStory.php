<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Behat\Stories;

use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact\ContactFactory;

#[AsFixture(name: 'behat-contacts', groups: ['behat-stories'])]
final class ContactStory extends Story
{
    public function build(): void
    {
        $this->addState(
            'john-doe',
            ContactFactory::createOne(['name' => 'John Doe'])
        );
    }
}
