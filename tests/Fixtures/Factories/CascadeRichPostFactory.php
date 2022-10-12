<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\CascadeRichPost;

final class CascadeRichPostFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return CascadeRichPost::class;
    }

    protected function getDefaults(): array
    {
        return [
            'title' => self::faker()->text(),
        ];
    }
}
