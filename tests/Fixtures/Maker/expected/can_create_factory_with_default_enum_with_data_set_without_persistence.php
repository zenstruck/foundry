<?php

namespace App\Factory;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Tests\Fixtures\PHP81\EntityWithEnum;
use Zenstruck\Foundry\Tests\Fixtures\PHP81\SomeEnum;

/**
 * @extends PersistentObjectFactory<EntityWithEnum>
 *
 * @method        EntityWithEnum|Proxy create(array|callable $attributes = [])
 * @method static EntityWithEnum|Proxy createOne(array $attributes = [])
 * @method static EntityWithEnum[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static EntityWithEnum[]|Proxy[] createSequence(iterable|callable $sequence)
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
            ->withoutPersisting()
            // ->afterInstantiate(function(EntityWithEnum $entityWithEnum): void {})
        ;
    }

    public static function class(): string
    {
        return EntityWithEnum::class;
    }
}
