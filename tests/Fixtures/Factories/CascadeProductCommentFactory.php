<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Cascade\ProductComment;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CascadeProductCommentFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return ProductComment::class;
    }

    protected function getDefaults(): array
    {
        return [
            'body' => self::faker()->word(),
            'product' => CascadeProductFactory::new(),
        ];
    }
}
