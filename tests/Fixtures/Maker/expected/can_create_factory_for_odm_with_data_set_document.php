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

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Tests\Fixtures\Document\ODMPost;

/**
 * @extends PersistentProxyObjectFactory<ODMPost>
 *
 * @method        ODMPost|Proxy                               create(array|callable $attributes = [])
 * @method static ODMPost|Proxy                               createOne(array $attributes = [])
 * @method static ODMPost|Proxy                               find(object|array|mixed $criteria)
 * @method static ODMPost|Proxy                               findOrCreate(array $attributes)
 * @method static ODMPost|Proxy                               first(string $sortedField = 'id')
 * @method static ODMPost|Proxy                               last(string $sortedField = 'id')
 * @method static ODMPost|Proxy                               random(array $attributes = [])
 * @method static ODMPost|Proxy                               randomOrCreate(array $attributes = [])
 * @method static DocumentRepository|ProxyRepositoryDecorator repository()
 * @method static ODMPost[]|Proxy[]                           all()
 * @method static ODMPost[]|Proxy[]                           createMany(int $number, array|callable $attributes = [])
 * @method static ODMPost[]|Proxy[]                           createSequence(iterable|callable $sequence)
 * @method static ODMPost[]|Proxy[]                           findBy(array $attributes)
 * @method static ODMPost[]|Proxy[]                           randomRange(int $min, int $max, array $attributes = [])
 * @method static ODMPost[]|Proxy[]                           randomSet(int $number, array $attributes = [])
 */
final class ODMPostFactory extends PersistentProxyObjectFactory
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
        return ODMPost::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'body' => self::faker()->text(),
            'createdAt' => self::faker()->dateTime(),
            'title' => self::faker()->text(),
            'viewCount' => self::faker()->randomNumber(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(ODMPost $oDMPost): void {})
        ;
    }
}
