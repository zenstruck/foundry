<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories\ODM;

use Doctrine\Common\Collections\ArrayCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMComment;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMPost;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMUser;

class PostFactory extends PersistentObjectFactory
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

    public static function class(): string
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
