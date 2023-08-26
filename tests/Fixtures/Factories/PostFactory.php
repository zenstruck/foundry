<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Repository\PostRepository;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @extends PersistentObjectFactory<Post>
 *
 * @method static Post|Proxy                     createOne(array $attributes = [])
 * @method static Post[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Post[]&Proxy[]                 createSequence(array|callable $sequence)
 * @method static Post|Proxy                     find(object|array|mixed $criteria)
 * @method static Post|Proxy                     findOrCreate(array $attributes)
 * @method static Post|Proxy                     first(string $sortedField = 'id')
 * @method static Post|Proxy                     last(string $sortedField = 'id')
 * @method static Post|Proxy                     random(array $attributes = [])
 * @method static Post|Proxy                     randomOrCreate(array $attributes = []))
 * @method static Post[]|Proxy[]                 all()
 * @method static Post[]|Proxy[]                 findBy(array $attributes)
 * @method static Post[]|Proxy[]                 randomSet(int $number, array $attributes = []))
 * @method static Post[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = []))
 * @method static PostRepository|RepositoryProxy repository()
 * @method        Post|Proxy                     create(array|callable $attributes = [])
 */
class PostFactory extends PersistentObjectFactory
{
    public function published(): static
    {
        return $this->withAttributes(static fn(): array => ['published_at' => self::faker()->dateTime()]);
    }

    public static function class(): string
    {
        return Post::class;
    }

    /**
     * @return Proxy<Post>
     * This method is here to test an edge case with the phpstan extension
     */
    public static function staticMethodCallWithSelf(): Proxy
    {
        return self::findOrCreate([]);
    }

    /**
     * @return FactoryCollection<Proxy<Post>>
     * This method is here to test an edge case with the phpstan extension
     */
    public function methodUsingThis(): FactoryCollection
    {
        return $this->sequence([]);
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
