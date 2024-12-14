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

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\AddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @extends PersistentObjectFactory<Contact>
 */
class ContactFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Contact::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->word(),
            'address' => AddressFactory::new(),
            'category' => CategoryFactory::new(),
        ];
    }
}
