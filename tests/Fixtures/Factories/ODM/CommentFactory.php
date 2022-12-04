<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories\ODM;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMComment;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMUser;

class CommentFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return ODMComment::class;
    }

    protected function getDefaults(): array
    {
        return [
            'user' => new ODMUser(self::faker()->userName()),
            'body' => self::faker()->sentence(),
        ];
    }
}
