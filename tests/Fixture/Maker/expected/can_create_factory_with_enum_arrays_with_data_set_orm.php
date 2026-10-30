<?php

namespace App\Factory;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\WithEnumArraysEntity;
use Zenstruck\Foundry\Tests\Fixture\IntBackedEnum;
use Zenstruck\Foundry\Tests\Fixture\StringBackedEnum;

/**
 * @extends PersistentObjectFactory<WithEnumArraysEntity>
 */
final class WithEnumArraysEntityFactory extends PersistentObjectFactory
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
        return WithEnumArraysEntity::class;
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
            'intEnums' => self::faker()->randomElements(IntBackedEnum::cases(), null),
            'stringEnums' => self::faker()->randomElements(StringBackedEnum::cases(), null),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(WithEnumArraysEntity $withEnumArraysEntity): void {})
        ;
    }
}
