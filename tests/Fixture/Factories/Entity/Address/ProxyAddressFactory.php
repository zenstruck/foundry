<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address;

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\Address;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @extends PersistentProxyObjectFactory<Address>
 */
final class ProxyAddressFactory extends PersistentProxyObjectFactory
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
}
