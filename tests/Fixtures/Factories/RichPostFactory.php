<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\RichPost;

final class RichPostFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return RichPost::class;
    }

    protected function getDefaults(): array
    {
        return [
            'title' => self::faker()->text(),
        ];
    }
}
