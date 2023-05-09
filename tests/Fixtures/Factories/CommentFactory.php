<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Comment;

final class CommentFactory extends PersistentObjectFactory
{
    protected function getDefaults(): array
    {
        return [
            'user' => UserFactory::new(),
            'body' => self::faker()->sentence(),
            'created_at' => self::faker()->dateTime(),
            'post' => PostFactory::new(),
        ];
    }

    public static function class(): string
    {
        return Comment::class;
    }
}
