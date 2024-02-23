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
use Zenstruck\Foundry\Tests\Fixture\Entity\WithEmbeddableEntity;

/**
 * @extends PersistentProxyObjectFactory<WithEmbeddableEntity>
 *
 * @method        WithEmbeddableEntity|Proxy                create(array|callable $attributes = [])
 * @method static WithEmbeddableEntity|Proxy                createOne(array $attributes = [])
 * @method static WithEmbeddableEntity|Proxy                find(object|array|mixed $criteria)
 * @method static WithEmbeddableEntity|Proxy                findOrCreate(array $attributes)
 * @method static WithEmbeddableEntity|Proxy                first(string $sortedField = 'id')
 * @method static WithEmbeddableEntity|Proxy                last(string $sortedField = 'id')
 * @method static WithEmbeddableEntity|Proxy                random(array $attributes = [])
 * @method static WithEmbeddableEntity|Proxy                randomOrCreate(array $attributes = [])
 * @method static EntityRepository|ProxyRepositoryDecorator repository()
 * @method static WithEmbeddableEntity[]|Proxy[]            all()
 * @method static WithEmbeddableEntity[]|Proxy[]            createMany(int $number, array|callable $attributes = [])
 * @method static WithEmbeddableEntity[]|Proxy[]            createSequence(iterable|callable $sequence)
 * @method static WithEmbeddableEntity[]|Proxy[]            findBy(array $attributes)
 * @method static WithEmbeddableEntity[]|Proxy[]            randomRange(int $min, int $max, array $attributes = [])
 * @method static WithEmbeddableEntity[]|Proxy[]            randomSet(int $number, array $attributes = [])
 */
final class WithEmbeddableEntityFactory extends PersistentProxyObjectFactory
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
        return WithEmbeddableEntity::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'embeddable' => EmbeddableFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(WithEmbeddableEntity $withEmbeddableEntity): void {})
        ;
    }
}
