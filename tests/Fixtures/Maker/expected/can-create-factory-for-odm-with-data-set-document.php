<?php

namespace App\Factory;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Document\Post;

/**
 * @extends ModelFactory<Post>
 *
 * @method        Post|Proxy     create(array|callable $attributes = [])
 * @method static Post|Proxy     createOne(array $attributes = [])
 * @method static Post|Proxy     find(object|array|mixed $criteria)
 * @method static Post|Proxy     findOrCreate(array $attributes)
 * @method static Post|Proxy     first(string $sortedField = 'id')
 * @method static Post|Proxy     last(string $sortedField = 'id')
 * @method static Post|Proxy     random(array $attributes = [])
 * @method static Post|Proxy     randomOrCreate(array $attributes = [])
 * @method static Post[]|Proxy[] all()
 * @method static Post[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Post[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Post[]|Proxy[] findBy(array $attributes)
 * @method static Post[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Post[]|Proxy[] randomSet(int $number, array $attributes = [])
 */
final class PostFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        return [
            'title' => self::faker()->text(),
            'body' => self::faker()->text(),
            'viewCount' => null, // TODO add INT ODM type manually
            'createdAt' => self::faker()->dateTime(),
            'comments' => null, // TODO add MANY ODM type manually
            'user' => null, // TODO add ONE ODM type manually
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Post $post): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Post::class;
    }
}
