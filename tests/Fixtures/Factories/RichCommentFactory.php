<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\RichComment;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RichCommentFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return RichComment::class;
    }

    protected function getDefaults(): array
    {
        return [
            'body' => self::faker()->text(),
            'post' => RichPostFactory::new(),
        ];
    }
}
