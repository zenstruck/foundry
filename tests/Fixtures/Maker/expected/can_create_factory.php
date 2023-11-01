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
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;

/**
 * @extends ModelFactory<Category>
 *
 * @method        Category|Proxy                   create(array|callable $attributes = [])
 * @method static Category|Proxy                   createOne(array $attributes = [])
 * @method static Category|Proxy                   find(object|array|mixed $criteria)
 * @method static Category|Proxy                   findOrCreate(array $attributes)
 * @method static Category|Proxy                   first(string $sortedField = 'id')
 * @method static Category|Proxy                   last(string $sortedField = 'id')
 * @method static Category|Proxy                   random(array $attributes = [])
 * @method static Category|Proxy                   randomOrCreate(array $attributes = [])
 * @method static EntityRepository|RepositoryProxy repository()
 * @method static Category[]|Proxy[]               all()
 * @method static Category[]|Proxy[]               createMany(int $number, array|callable $attributes = [])
 * @method static Category[]|Proxy[]               createSequence(iterable|callable $sequence)
 * @method static Category[]|Proxy[]               findBy(array $attributes)
 * @method static Category[]|Proxy[]               randomRange(int $min, int $max, array $attributes = [])
 * @method static Category[]|Proxy[]               randomSet(int $number, array $attributes = [])
 */
final class CategoryFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->text(255),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Category $category): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Category::class;
    }
}
