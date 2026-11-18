<?php

namespace App\Factory;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\WithUniqueFieldsEntity;
use Zenstruck\Foundry\Tests\Fixture\StringBackedEnum;

/**
 * @extends PersistentObjectFactory<WithUniqueFieldsEntity>
 */
final class WithUniqueFieldsEntityFactory extends PersistentObjectFactory
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
        return WithUniqueFieldsEntity::class;
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
            'nullableUniqueDate' => self::faker()->optional()->passthrough(\DateTimeImmutable::createFromMutable(self::faker()->unique()->dateTime())),
            'nullableUniqueField' => self::faker()->optional()->passthrough(self::faker()->unique()->text()),
            'uniqueByColumnName' => self::faker()->unique()->text(),
            'uniqueByFieldName' => self::faker()->unique()->text(),
            'uniqueEnum' => self::faker()->unique()->randomElement(StringBackedEnum::cases()),
            'uniqueField' => self::faker()->unique()->text(),
            'uniqueTogether1' => self::faker()->randomNumber(),
            'uniqueTogether2' => self::faker()->randomNumber(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(WithUniqueFieldsEntity $withUniqueFieldsEntity): void {})
        ;
    }
}
