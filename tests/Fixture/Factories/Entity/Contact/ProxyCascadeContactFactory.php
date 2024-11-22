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
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact\CascadeContact;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\ProxyCascadeAddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\ProxyCascadeCategoryFactory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @extends PersistentProxyObjectFactory<CascadeContact>
 */
final class ProxyCascadeContactFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return CascadeContact::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->word(),
            'category' => ProxyCascadeCategoryFactory::new(),
            'address' => ProxyCascadeAddressFactory::new(),
        ];
    }
}
