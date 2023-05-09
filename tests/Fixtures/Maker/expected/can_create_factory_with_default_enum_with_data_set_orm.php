<?php

namespace App\Factory;

use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\Tests\Fixtures\PHP81\EntityWithEnum;
use Zenstruck\Foundry\Tests\Fixtures\PHP81\SomeEnum;

/**
 * @extends PersistentObjectFactory<EntityWithEnum>
 *
 * @method        EntityWithEnum|Proxy create(array|callable $attributes = [])
 * @method static EntityWithEnum|Proxy createOne(array $attributes = [])
 * @method static EntityWithEnum|Proxy find(object|array|mixed $criteria)
 * @method static EntityWithEnum|Proxy findOrCreate(array $attributes)
 * @method static EntityWithEnum|Proxy first(string $sortedField = 'id')
 * @method static EntityWithEnum|Proxy last(string $sortedField = 'id')
 * @method static EntityWithEnum|Proxy random(array $attributes = [])
 * @method static EntityWithEnum|Proxy randomOrCreate(array $attributes = [])
 * @method static EntityRepository|RepositoryProxy repository()
 * @method static EntityWithEnum[]|Proxy[] all()
 * @method static EntityWithEnum[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static EntityWithEnum[]|Proxy[] createSequence(iterable|callable $sequence)
 * @method static EntityWithEnum[]|Proxy[] findBy(array $attributes)
 * @method static EntityWithEnum[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static EntityWithEnum[]|Proxy[] randomSet(int $number, array $attributes = [])
 */
final class EntityWithEnumFactory extends PersistentObjectFactory
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
            'enum' => self::faker()->randomElement(SomeEnum::cases()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(EntityWithEnum $entityWithEnum): void {})
        ;
    }

    public static function class(): string
    {
        return EntityWithEnum::class;
    }
}
