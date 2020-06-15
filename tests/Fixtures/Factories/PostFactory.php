<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class PostFactory extends ModelFactory
{
    public function published(): self
    {
        return $this->addState(function() {
            return ['published_at' => self::faker()->dateTime];
        });
    }

    protected static function getClass(): string
    {
        return Post::class;
    }

    protected function getDefaults(): array
    {
        return [
            'title' => self::faker()->sentence,
            'body' => self::faker()->sentence,
        ];
    }
}
