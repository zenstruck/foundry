<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories\ODM;

use Doctrine\Common\Collections\ArrayCollection;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Document\Comment;
use Zenstruck\Foundry\Tests\Fixtures\Document\Post;
use Zenstruck\Foundry\Tests\Fixtures\Document\User;

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
                new Comment(new User('user'), 'body'),
                new Comment(new User('user'), 'body'),
            ]),
        ]);
    }

    protected static function getClass(): string
    {
        return Post::class;
    }

    protected function getDefaults(): array
    {
        return [
            'title' => self::faker()->sentence(),
            'body' => self::faker()->sentence(),
            'user' => new User('user'),
        ];
    }
}
