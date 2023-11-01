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

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\User;

final class UserFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->name(),
        ];
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
