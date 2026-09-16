<?php

namespace App\Factory;

use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\ObjectWithNonWriteable;

/**
 * @extends ObjectFactory<ObjectWithNonWriteable>
 */
final class ObjectWithNonWriteableFactory extends ObjectFactory
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
        return ObjectWithNonWriteable::class;
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
            'baz' => self::faker()->sentence(),
            'foo' => self::faker()->sentence(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(ObjectWithNonWriteable $objectWithNonWriteable): void {})
        ;
    }
}
