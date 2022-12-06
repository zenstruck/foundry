<?php

namespace App\Factory;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Repository\PostRepository;

/**
 * @extends ModelFactory<Post>
 *
 * @method        Post|Proxy create(array|callable $attributes = [])
 * @method static Post|Proxy createOne(array $attributes = [])
 * @method static Post|Proxy find(object|array|mixed $criteria)
 * @method static Post|Proxy findOrCreate(array $attributes)
 * @method static Post|Proxy first(string $sortedField = 'id')
 * @method static Post|Proxy last(string $sortedField = 'id')
 * @method static Post|Proxy random(array $attributes = [])
 * @method static Post|Proxy randomOrCreate(array $attributes = [])
 * @method static PostRepository|RepositoryProxy repository()
 * @method static Post[]|Proxy[] all()
 * @method static Post[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Post[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Post[]|Proxy[] findBy(array $attributes)
 * @method static Post[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Post[]|Proxy[] randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<Post> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<Post> createOne(array $attributes = [])
 * @phpstan-method static Proxy<Post> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<Post> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<Post> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<Post> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<Post> random(array $attributes = [])
 * @phpstan-method static Proxy<Post> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<PostRepository> repository()
 * @phpstan-method static list<Proxy<Post>> all()
 * @phpstan-method static list<Proxy<Post>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<Post>> createSequence(array|callable $sequence)
 * @phpstan-method static list<Proxy<Post>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<Post>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<Post>> randomSet(int $number, array $attributes = [])
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
            'body' => self::faker()->text(),
            'createdAt' => self::faker()->dateTime(),
            'title' => self::faker()->text(255),
            'viewCount' => self::faker()->randomNumber(),
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
