<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class PostFactory extends PersistentObjectFactory
{
    public function published(): static
    {
        return $this->addState(static fn(): array => ['published_at' => self::faker()->dateTime()]);
    }

    public static function class(): string
    {
        return Post::class;
    }

    protected function getDefaults(): array
    {
        return [
            'title' => self::faker()->sentence(),
            'body' => self::faker()->sentence(),
        ];
    }

    protected function initialize()
    {
        return $this
            ->instantiateWith(
                (new Instantiator())->allowExtraAttributes(['extraCategoryBeforeInstantiate', 'extraCategoryAfterInstantiate'])
            )
            ->beforeInstantiate(function (array $attributes): array {
                if (isset($attributes['extraCategoryBeforeInstantiate'])) {
                    $attributes['category'] = $attributes['extraCategoryBeforeInstantiate'];
                }
                unset($attributes['extraCategoryBeforeInstantiate']);

                return $attributes;
            })
            ->afterInstantiate(function (Post $object, array $attributes): void {
                if (isset($attributes['extraCategoryAfterInstantiate'])) {
                    $object->setCategory($attributes['extraCategoryAfterInstantiate']);
                }
            });
    }
}
