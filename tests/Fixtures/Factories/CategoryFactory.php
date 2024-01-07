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
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CategoryFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Category::class;
    }

    protected function defaults(): array|callable
    {
        return ['name' => self::faker()->sentence()];
    }

    protected function initialize(): static
    {
        return $this
            ->instantiateWith(
                Instantiator::withConstructor()->allowExtra('extraPostsBeforeInstantiate', 'extraPostsAfterInstantiate')
            )
            ->beforeInstantiate(function(array $attributes): array {
                if (isset($attributes['extraPostsBeforeInstantiate'])) {
                    $attributes['posts'] = $attributes['extraPostsBeforeInstantiate'];
                }

                unset($attributes['extraPostsBeforeInstantiate']);

                return $attributes;
            })
            ->afterInstantiate(function(Category $object, array $attributes): void {
                foreach ($attributes['extraPostsAfterInstantiate'] ?? [] as $extraPost) {
                    $object->addPost($extraPost);
                }
            });
    }
}
