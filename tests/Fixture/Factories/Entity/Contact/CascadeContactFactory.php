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
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact\CascadeContact;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Address\CascadeAddressFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CascadeCategoryFactory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @extends PersistentObjectFactory<CascadeContact>
 */
final class CascadeContactFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return CascadeContact::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->word(),
//            'category' => CascadeCategoryFactory::new(),
            'address' => CascadeAddressFactory::new(),
        ];
    }
}
