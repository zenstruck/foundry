<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Factory;

use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Comment;

/**
 * @extends PersistentProxyObjectFactory<Comment>
 *
 * @method        Comment|Proxy                             create(array|callable $attributes = [])
 * @method static Comment|Proxy                             createOne(array $attributes = [])
 * @method static Comment|Proxy                             find(object|array|mixed $criteria)
 * @method static Comment|Proxy                             findOrCreate(array $attributes)
 * @method static Comment|Proxy                             first(string $sortedField = 'id')
 * @method static Comment|Proxy                             last(string $sortedField = 'id')
 * @method static Comment|Proxy                             random(array $attributes = [])
 * @method static Comment|Proxy                             randomOrCreate(array $attributes = [])
 * @method static EntityRepository|ProxyRepositoryDecorator repository()
 * @method static Comment[]|Proxy[]                         all()
 * @method static Comment[]|Proxy[]                         createMany(int $number, array|callable $attributes = [])
 * @method static Comment[]|Proxy[]                         createSequence(iterable|callable $sequence)
 * @method static Comment[]|Proxy[]                         findBy(array $attributes)
 * @method static Comment[]|Proxy[]                         randomRange(int $min, int $max, array $attributes = [])
 * @method static Comment[]|Proxy[]                         randomSet(int $number, array $attributes = [])
 */
final class CommentFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Comment::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'approved' => self::faker()->boolean(),
            'body' => self::faker()->text(),
            'createdAt' => self::faker()->dateTime(),
            'post' => PostFactory::new(),
            'user' => null, // TODO add Zenstruck\Foundry\Tests\Fixtures\Entity\User type manually
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Comment $comment): void {})
        ;
    }
}
