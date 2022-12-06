<?php

namespace App\Factory;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\Entity\EntityWithRelations;
use Zenstruck\Foundry\Tests\Fixtures\Factories\CategoryFactory;

/**
 * @extends ModelFactory<EntityWithRelations>
 *
 * @method        EntityWithRelations|Proxy create(array|callable $attributes = [])
 * @method static EntityWithRelations|Proxy createOne(array $attributes = [])
 * @method static EntityWithRelations|Proxy find(object|array|mixed $criteria)
 * @method static EntityWithRelations|Proxy findOrCreate(array $attributes)
 * @method static EntityWithRelations|Proxy first(string $sortedField = 'id')
 * @method static EntityWithRelations|Proxy last(string $sortedField = 'id')
 * @method static EntityWithRelations|Proxy random(array $attributes = [])
 * @method static EntityWithRelations|Proxy randomOrCreate(array $attributes = [])
 * @method static EntityWithRelations[]|Proxy[] all()
 * @method static EntityWithRelations[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static EntityWithRelations[]|Proxy[] createSequence(array|callable $sequence)
 * @method static EntityWithRelations[]|Proxy[] findBy(array $attributes)
 * @method static EntityWithRelations[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static EntityWithRelations[]|Proxy[] randomSet(int $number, array $attributes = [])
 */
final class EntityWithRelationsFactory extends ModelFactory
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
            'manyToOne' => CategoryFactory::new(),
            'manyToOneWithNotExistingFactory' => BrandFactory::new(),
            'oneToOne' => CategoryFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(EntityWithRelations $entityWithRelations): void {})
        ;
    }

    protected static function getClass(): string
    {
        return EntityWithRelations::class;
    }
}
