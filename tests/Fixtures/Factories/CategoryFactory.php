<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CategoryFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Category::class;
    }

    protected function getDefaults(): array
    {
        return ['name' => self::faker()->sentence()];
    }

    protected function initialize()
    {
        return $this
            ->instantiateWith(
                (new Instantiator())->allowExtraAttributes(['extraPostsBeforeInstantiate', 'extraPostsAfterInstantiate'])
            )
            ->beforeInstantiate(function (array $attributes): array {
                if (isset($attributes['extraPostsBeforeInstantiate'])) {
                    $attributes['posts'] = $attributes['extraPostsBeforeInstantiate'];
                }

                unset($attributes['extraPostsBeforeInstantiate']);

                return $attributes;
            })
            ->afterInstantiate(function (Category $object, array $attributes): void {
                foreach ($attributes['extraPostsAfterInstantiate'] ?? [] as $extraPost) {
                    $object->addPost($extraPost);
                }
            });
    }
}
