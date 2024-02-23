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
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Entity\Repository\GenericEntityRepository;

/**
 * @extends PersistentProxyObjectFactory<GenericEntity>
 *
 * @method        GenericEntity|Proxy                              create(array|callable $attributes = [])
 * @method static GenericEntity|Proxy                              createOne(array $attributes = [])
 * @method static GenericEntity|Proxy                              find(object|array|mixed $criteria)
 * @method static GenericEntity|Proxy                              findOrCreate(array $attributes)
 * @method static GenericEntity|Proxy                              first(string $sortedField = 'id')
 * @method static GenericEntity|Proxy                              last(string $sortedField = 'id')
 * @method static GenericEntity|Proxy                              random(array $attributes = [])
 * @method static GenericEntity|Proxy                              randomOrCreate(array $attributes = [])
 * @method static GenericEntityRepository|ProxyRepositoryDecorator repository()
 * @method static GenericEntity[]|Proxy[]                          all()
 * @method static GenericEntity[]|Proxy[]                          createMany(int $number, array|callable $attributes = [])
 * @method static GenericEntity[]|Proxy[]                          createSequence(iterable|callable $sequence)
 * @method static GenericEntity[]|Proxy[]                          findBy(array $attributes)
 * @method static GenericEntity[]|Proxy[]                          randomRange(int $min, int $max, array $attributes = [])
 * @method static GenericEntity[]|Proxy[]                          randomSet(int $number, array $attributes = [])
 *
 * @psalm-method        GenericEntity&Proxy<GenericEntity> create(array|callable $attributes = [])
 * @psalm-method static GenericEntity&Proxy<GenericEntity> createOne(array $attributes = [])
 * @psalm-method static GenericEntity&Proxy<GenericEntity> find(object|array|mixed $criteria)
 * @psalm-method static GenericEntity&Proxy<GenericEntity> findOrCreate(array $attributes)
 * @psalm-method static GenericEntity&Proxy<GenericEntity> first(string $sortedField = 'id')
 * @psalm-method static GenericEntity&Proxy<GenericEntity> last(string $sortedField = 'id')
 * @psalm-method static GenericEntity&Proxy<GenericEntity> random(array $attributes = [])
 * @psalm-method static GenericEntity&Proxy<GenericEntity> randomOrCreate(array $attributes = [])
 * @psalm-method static ProxyRepositoryDecorator<GenericEntity, EntityRepository> repository()
 * @psalm-method static list<GenericEntity&Proxy<GenericEntity>> all()
 * @psalm-method static list<GenericEntity&Proxy<GenericEntity>> createMany(int $number, array|callable $attributes = [])
 * @psalm-method static list<GenericEntity&Proxy<GenericEntity>> createSequence(iterable|callable $sequence)
 * @psalm-method static list<GenericEntity&Proxy<GenericEntity>> findBy(array $attributes)
 * @psalm-method static list<GenericEntity&Proxy<GenericEntity>> randomRange(int $min, int $max, array $attributes = [])
 * @psalm-method static list<GenericEntity&Proxy<GenericEntity>> randomSet(int $number, array $attributes = [])
 */
final class GenericEntityFactory extends PersistentProxyObjectFactory
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
        return GenericEntity::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'prop1' => self::faker()->text(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(GenericEntity $genericEntity): void {})
        ;
    }
}
