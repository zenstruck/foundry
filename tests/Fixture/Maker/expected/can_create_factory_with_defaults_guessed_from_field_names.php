<?php

namespace App\Factory;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\WithGuessableFieldNames;

/**
 * @extends PersistentObjectFactory<WithGuessableFieldNames>
 */
final class WithGuessableFieldNamesFactory extends PersistentObjectFactory
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
        return WithGuessableFieldNames::class;
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
            'cityName' => self::faker()->city(),
            'company' => self::faker()->optional()->company(),
            'country' => self::faker()->country(),
            'countryCode' => self::faker()->countryCode(),
            'email' => self::faker()->safeEmail(),
            'firstName' => self::faker()->firstName(),
            'job' => self::faker()->jobTitle(),
            'lastName' => self::faker()->lastName(),
            'latitude' => self::faker()->latitude(),
            'longitude' => self::faker()->longitude(),
            'phone_number' => self::faker()->phoneNumber(),
            'postcode' => self::faker()->randomNumber(),
            'username' => self::faker()->text(20),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(WithGuessableFieldNames $withGuessableFieldNames): void {})
        ;
    }
}
