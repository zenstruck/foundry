<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class PostFactory extends PersistentProxyObjectFactory
{
    public function published(): static
    {
        return $this->with(static fn(): array => ['published_at' => self::faker()->dateTime()]);
    }

    public function publishedWithLegacyMethod(): static
    {
        return $this->addState(static fn(): array => ['published_at' => self::faker()->dateTime()]);
    }

    public static function class(): string
    {
        return Post::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'title' => self::faker()->sentence(),
            'body' => self::faker()->sentence(),
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->instantiateWith(
                Instantiator::withConstructor()->allowExtra('extraCategoryBeforeInstantiate', 'extraCategoryAfterInstantiate')
            )
            ->beforeInstantiate(function(array $attributes): array {
                if (isset($attributes['extraCategoryBeforeInstantiate'])) {
                    $attributes['category'] = $attributes['extraCategoryBeforeInstantiate'];
                }
                unset($attributes['extraCategoryBeforeInstantiate']);

                return $attributes;
            })
            ->afterInstantiate(function(Post $object, array $attributes): void {
                if (isset($attributes['extraCategoryAfterInstantiate'])) {
                    $object->setCategory($attributes['extraCategoryAfterInstantiate']);
                }
            });
    }
}
