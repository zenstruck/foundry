<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixtures\Factories\ODM;

use Doctrine\Common\Collections\ArrayCollection;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMComment;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMPost;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMUser;

class PostFactory extends ModelFactory
{
    public function published(): static
    {
        return $this->addState(static fn(): array => ['published_at' => self::faker()->dateTime()]);
    }

    public function withComments(): static
    {
        return $this->addState(static fn(): array => [
            'comments' => new ArrayCollection([
                new ODMComment(new ODMUser('user'), 'body'),
                new ODMComment(new ODMUser('user'), 'body'),
            ]),
        ]);
    }

    protected static function getClass(): string
    {
        return ODMPost::class;
    }

    protected function getDefaults(): array
    {
        return [
            'title' => self::faker()->sentence(),
            'body' => self::faker()->sentence(),
            'user' => new ODMUser('user'),
        ];
    }
}
