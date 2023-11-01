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

use App\Factory\Cascade\BrandFactory;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Tests\Fixtures\Entity\EntityWithRelations;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;

/**
 * @extends PersistentProxyObjectFactory<EntityWithRelations>
 *
 * @method        EntityWithRelations|Proxy                 create(array|callable $attributes = [])
 * @method static EntityWithRelations|Proxy                 createOne(array $attributes = [])
 * @method static EntityWithRelations|Proxy                 find(object|array|mixed $criteria)
 * @method static EntityWithRelations|Proxy                 findOrCreate(array $attributes)
 * @method static EntityWithRelations|Proxy                 first(string $sortedField = 'id')
 * @method static EntityWithRelations|Proxy                 last(string $sortedField = 'id')
 * @method static EntityWithRelations|Proxy                 random(array $attributes = [])
 * @method static EntityWithRelations|Proxy                 randomOrCreate(array $attributes = [])
 * @method static EntityRepository|ProxyRepositoryDecorator repository()
 * @method static EntityWithRelations[]|Proxy[]             all()
 * @method static EntityWithRelations[]|Proxy[]             createMany(int $number, array|callable $attributes = [])
 * @method static EntityWithRelations[]|Proxy[]             createSequence(iterable|callable $sequence)
 * @method static EntityWithRelations[]|Proxy[]             findBy(array $attributes)
 * @method static EntityWithRelations[]|Proxy[]             randomRange(int $min, int $max, array $attributes = [])
 * @method static EntityWithRelations[]|Proxy[]             randomSet(int $number, array $attributes = [])
 */
final class EntityWithRelationsFactory extends PersistentProxyObjectFactory
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
        return EntityWithRelations::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'manyToOne' => CategoryFactory::new(),
            'manyToOneWithNotExistingFactory' => BrandFactory::new(),
            'oneToOne' => CategoryFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(EntityWithRelations $entityWithRelations): void {})
        ;
    }
}
