<?php

namespace App\Factory;

use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\ObjectWithNullableProperties;
use Zenstruck\Foundry\Tests\Fixture\StringBackedEnum;

/**
 * @extends ObjectFactory<ObjectWithNullableProperties>
 */
final class ObjectWithNullablePropertiesFactory extends ObjectFactory
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
        return ObjectWithNullableProperties::class;
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
            'nullable' => self::faker()->optional()->sentence(),
            'nullableDate' => self::faker()->optional()->passthrough(\DateTimeImmutable::createFromMutable(self::faker()->dateTime())),
            'nullableEnum' => self::faker()->optional()->randomElement(StringBackedEnum::cases()),
            'nullableWithRequiredConstructorParameter' => self::faker()->sentence(),
            'nullableWithRequiredSetter' => self::faker()->sentence(),
            'required' => self::faker()->sentence(),
            'snake_case_nullable_with_required_setter' => self::faker()->sentence(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(ObjectWithNullableProperties $objectWithNullableProperties): void {})
        ;
    }
}
