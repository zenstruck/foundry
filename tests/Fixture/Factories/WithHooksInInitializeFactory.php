<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Tests\Fixture\Factories;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address\StandardAddress;

/**
 * @author Nicolas Philippe <nikophil@gmail.com>
 * @extends PersistentObjectFactory<StandardAddress>
 */
final class WithHooksInInitializeFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'city' => self::faker()->city(),
        ];
    }

    public static function class(): string
    {
        return StandardAddress::class;
    }

    protected function initialize(): static
    {
        return $this
            ->beforeInstantiate(
                function (array $parameters, string $class, WithHooksInInitializeFactory $factory) {
                    if (!$factory->isPersisting()) {
                        $parameters['city'] = 'beforeInstantiate';
                    }

                    return $parameters;
                }
            )
            ->afterInstantiate(
                function (StandardAddress $object, array $parameters, WithHooksInInitializeFactory $factory) {
                    if (!$factory->isPersisting()) {
                        $object->setCity("{$object->getCity()} - afterInstantiate");
                    }
                }
            );
    }
}
