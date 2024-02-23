<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Contact;

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact\StandardContact;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\ProxyAddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\ProxyCategoryFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @extends PersistentProxyObjectFactory<StandardContact>
 */
final class ProxyContactFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return StandardContact::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->word(),
//            'category' => ProxyCategoryFactory::new(),
            'address' => ProxyAddressFactory::new(),
        ];
    }
}
