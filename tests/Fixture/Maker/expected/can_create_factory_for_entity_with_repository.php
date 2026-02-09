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
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\Entity\Repository\GenericEntityRepository;

/**
 * @extends PersistentObjectFactory<GenericEntity>
 *
 * @method        GenericEntity                               create(array|callable $attributes = [])
 * @method static GenericEntity                               createOne(array $attributes = [])
 * @method static GenericEntity                               find(object|array|mixed $criteria)
 * @method static GenericEntity                               findOrCreate(array $attributes)
 * @method static GenericEntity                               first(string $sortBy = 'id')
 * @method static GenericEntity                               last(string $sortBy = 'id')
 * @method static GenericEntity                               random(array $attributes = [])
 * @method static GenericEntity                               randomOrCreate(array $attributes = [])
 * @method static GenericEntityRepository|RepositoryDecorator repository()
 * @method static GenericEntity[]                             all()
 * @method static GenericEntity[]                             createMany(int $number, array|callable $attributes = [])
 * @method static GenericEntity[]                             createSequence(iterable|callable $sequence)
 * @method static GenericEntity[]                             findBy(array $attributes)
 * @method static GenericEntity[]                             randomRange(int $min, int $max, array $attributes = [])
 * @method static GenericEntity[]                             randomRangeOrCreate(int $min, int $max, array $attributes = [])
 * @method static GenericEntity[]                             randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        GenericEntity create(array|callable $attributes = [])
 * @phpstan-method static GenericEntity createOne(array $attributes = [])
 * @phpstan-method static GenericEntity find(object|array|mixed $criteria)
 * @phpstan-method static GenericEntity findOrCreate(array $attributes)
 * @phpstan-method static GenericEntity first(string $sortBy = 'id')
 * @phpstan-method static GenericEntity last(string $sortBy = 'id')
 * @phpstan-method static GenericEntity random(array $attributes = [])
 * @phpstan-method static GenericEntity randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryDecorator<GenericEntity, EntityRepository<GenericEntity>> repository()
 * @phpstan-method static list<GenericEntity> all()
 * @phpstan-method static list<GenericEntity> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<GenericEntity> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<GenericEntity> findBy(array $attributes)
 * @phpstan-method static list<GenericEntity> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<GenericEntity> randomRangeOrCreate(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<GenericEntity> randomSet(int $number, array $attributes = [])
 */
final class GenericEntityFactory extends PersistentObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return GenericEntity::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'prop1' => self::faker()->text(),
            'propInteger' => self::faker()->randomNumber(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(GenericEntity $genericEntity): void {})
        ;
    }
}
