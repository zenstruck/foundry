<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\Address;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AddressFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Address::class;
    }

    protected function defaults(): array|callable
    {
        return ['value' => 'Some address'];
    }
}
