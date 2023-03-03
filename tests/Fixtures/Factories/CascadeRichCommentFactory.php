<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\CascadeRichComment;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CascadeRichCommentFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return CascadeRichComment::class;
    }

    protected function getDefaults(): array
    {
        return [
            'body' => self::faker()->text(),
            'post' => CascadeRichPostFactory::new(),
        ];
    }
}
