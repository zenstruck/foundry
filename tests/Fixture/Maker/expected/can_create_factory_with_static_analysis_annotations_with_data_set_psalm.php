<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Factory;

use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;

/**
 * @extends PersistentObjectFactory<Category>
 *
 * @method        Category                             create(array|callable $attributes = [])
 * @method static Category                             createOne(array $attributes = [])
 * @method static Category                             find(object|array|mixed $criteria)
 * @method static Category                             findOrCreate(array $attributes)
 * @method static Category                             first(string $sortBy = 'id')
 * @method static Category                             last(string $sortBy = 'id')
 * @method static Category                             random(array $attributes = [])
 * @method static Category                             randomOrCreate(array $attributes = [])
 * @method static EntityRepository|RepositoryDecorator repository()
 * @method static Category[]                           all()
 * @method static Category[]                           createMany(int $number, array|callable $attributes = [])
 * @method static Category[]                           createSequence(iterable|callable $sequence)
 * @method static Category[]                           findBy(array $attributes)
 * @method static Category[]                           randomRange(int $min, int $max, array $attributes = [])
 * @method static Category[]                           randomRangeOrCreate(int $min, int $max, array $attributes = [])
 * @method static Category[]                           randomSet(int $number, array $attributes = [])
 *
 * @psalm-method        Category create(array|callable $attributes = [])
 * @psalm-method static Category createOne(array $attributes = [])
 * @psalm-method static Category find(object|array|mixed $criteria)
 * @psalm-method static Category findOrCreate(array $attributes)
 * @psalm-method static Category first(string $sortBy = 'id')
 * @psalm-method static Category last(string $sortBy = 'id')
 * @psalm-method static Category random(array $attributes = [])
 * @psalm-method static Category randomOrCreate(array $attributes = [])
 * @psalm-method static RepositoryDecorator<Category, EntityRepository<Category>> repository()
 * @psalm-method static list<Category> all()
 * @psalm-method static list<Category> createMany(int $number, array|callable $attributes = [])
 * @psalm-method static list<Category> createSequence(iterable|callable $sequence)
 * @psalm-method static list<Category> findBy(array $attributes)
 * @psalm-method static list<Category> randomRange(int $min, int $max, array $attributes = [])
 * @psalm-method static list<Category> randomRangeOrCreate(int $min, int $max, array $attributes = [])
 * @psalm-method static list<Category> randomSet(int $number, array $attributes = [])
 */
final class CategoryFactory extends PersistentObjectFactory
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
        return Category::class;
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
            'name' => self::faker()->text(255),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Category $category): void {})
        ;
    }
}
