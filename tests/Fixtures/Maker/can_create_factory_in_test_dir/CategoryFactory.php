<?php

namespace App\Tests\Factory;

use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Category>
 *
 * @method Category|Proxy create(array|callable $attributes = [])
 * @method static Category|Proxy createOne(array $attributes = [])
 * @method static Category|Proxy find(object|array|mixed $criteria)
 * @method static Category|Proxy findOrCreate(array $attributes)
 * @method static Category|Proxy first(string $sortedField = 'id')
 * @method static Category|Proxy last(string $sortedField = 'id')
 * @method static Category|Proxy random(array $attributes = [])
 * @method static Category|Proxy randomOrCreate(array $attributes = [])
 * @method static list<Category>|list<Proxy> all()
 * @method static list<Category>|list<Proxy> createMany(int $number, array|callable $attributes = [])
 * @method static list<Category>|list<Proxy> createSequence(array|callable $sequence)
 * @method static list<Category>|list<Proxy> findBy(array $attributes)
 * @method static list<Category>|list<Proxy> randomRange(int $min, int $max, array $attributes = [])
 * @method static list<Category>|list<Proxy> randomSet(int $number, array $attributes = [])
 */
final class CategoryFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();

        // TODO inject services if required (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services)
    }

    protected function getDefaults(): array
    {
        return [
            // TODO add your default values here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories)
            'name' => self::faker()->text(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Category $category): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Category::class;
    }
}
