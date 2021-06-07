<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories\ODM;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Document\Comment;

class CommentFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return Comment::class;
    }

    protected function getDefaults(): array
    {
        return [
            'user' => self::faker()->userName(),
            'body' => self::faker()->sentence(),
        ];
    }
}
