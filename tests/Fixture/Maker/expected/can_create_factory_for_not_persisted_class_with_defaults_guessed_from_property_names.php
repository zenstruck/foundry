<?php

namespace App\Factory;

use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\ObjectWithGuessablePropertyNames;

/**
 * @extends ObjectFactory<ObjectWithGuessablePropertyNames>
 */
final class ObjectWithGuessablePropertyNamesFactory extends ObjectFactory
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
        return ObjectWithGuessablePropertyNames::class;
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
            'email' => self::faker()->safeEmail(),
            'firstName' => self::faker()->firstName(),
            'latitude' => self::faker()->latitude(),
            'longitude' => self::faker()->longitude(),
            'name' => self::faker()->sentence(),
            'phone' => self::faker()->optional()->phoneNumber(),
            'postcode' => self::faker()->randomNumber(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(ObjectWithGuessablePropertyNames $objectWithGuessablePropertyNames): void {})
        ;
    }
}
