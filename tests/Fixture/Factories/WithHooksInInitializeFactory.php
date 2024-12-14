<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Factories;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address;

/**
 * @author Nicolas Philippe <nikophil@gmail.com>
 * @extends PersistentObjectFactory<Address>
 */
final class WithHooksInInitializeFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Address::class;
    }

    protected function defaults(): array
    {
        return [
            'city' => self::faker()->city(),
        ];
    }

    protected function initialize(): static
    {
        return $this
            ->beforeInstantiate(
                function(array $parameters, string $class, WithHooksInInitializeFactory $factory) {
                    if (!$factory->isPersisting()) {
                        $parameters['city'] = 'beforeInstantiate';
                    }

                    return $parameters;
                }
            )
            ->afterInstantiate(
                function(Address $object, array $parameters, WithHooksInInitializeFactory $factory) {
                    if (!$factory->isPersisting()) {
                        $object->setCity("{$object->getCity()} - afterInstantiate");
                    }
                }
            );
    }
}
