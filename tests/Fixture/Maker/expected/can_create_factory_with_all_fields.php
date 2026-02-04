<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Factory;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\GenericEntity;
use Zenstruck\Foundry\Tests\Fixture\IntBackedEnum;
use Zenstruck\Foundry\Tests\Fixture\StringBackedEnum;

/**
 * @extends PersistentObjectFactory<GenericEntity>
 */
final class GenericEntityFactory extends PersistentObjectFactory
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
        return GenericEntity::class;
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
            'bool' => self::faker()->boolean(),
            'date' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'dateMutable' => self::faker()->dateTime(),
            'float' => self::faker()->randomFloat(),
            'intEnum' => self::faker()->randomElement(IntBackedEnum::cases()),
            'prop1' => self::faker()->text(),
            'propInteger' => self::faker()->randomNumber(),
            'stringEnum' => self::faker()->randomElement(StringBackedEnum::cases()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(GenericEntity $genericEntity): void {})
        ;
    }
}
