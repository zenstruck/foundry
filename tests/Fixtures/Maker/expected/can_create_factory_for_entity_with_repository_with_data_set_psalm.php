<?php

namespace App\Factory;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Post;
use Zenstruck\Foundry\Tests\Fixtures\Repository\PostRepository;

/**
 * @extends PersistentObjectFactory<Post>
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
 * @method static Post[]|Proxy[] createSequence(iterable|callable $sequence)
 * @method static Post[]|Proxy[] findBy(array $attributes)
 * @method static Post[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Post[]|Proxy[] randomSet(int $number, array $attributes = [])
 *
 * @psalm-method        Proxy<Post> create(array|callable $attributes = [])
 * @psalm-method static Proxy<Post> createOne(array $attributes = [])
 * @psalm-method static Proxy<Post> find(object|array|mixed $criteria)
 * @psalm-method static Proxy<Post> findOrCreate(array $attributes)
 * @psalm-method static Proxy<Post> first(string $sortedField = 'id')
 * @psalm-method static Proxy<Post> last(string $sortedField = 'id')
 * @psalm-method static Proxy<Post> random(array $attributes = [])
 * @psalm-method static Proxy<Post> randomOrCreate(array $attributes = [])
 * @psalm-method static RepositoryProxy<Post> repository()
 * @psalm-method static list<Proxy<Post>> all()
 * @psalm-method static list<Proxy<Post>> createMany(int $number, array|callable $attributes = [])
 * @psalm-method static list<Proxy<Post>> createSequence(iterable|callable $sequence)
 * @psalm-method static list<Proxy<Post>> findBy(array $attributes)
 * @psalm-method static list<Proxy<Post>> randomRange(int $min, int $max, array $attributes = [])
 * @psalm-method static list<Proxy<Post>> randomSet(int $number, array $attributes = [])
 */
final class PostFactory extends PersistentObjectFactory
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

    public static function class(): string
    {
        return Post::class;
    }
}
