<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Comment;

final class CommentFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'user' => UserFactory::new(),
            'body' => self::faker()->sentence,
            'created_at' => self::faker()->dateTime,
            'post' => PostFactory::new(),
        ];
    }

    protected static function getClass(): string
    {
        return Comment::class;
    }
}
