<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category;

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @extends PersistentProxyObjectFactory<Category>
 */
final class ProxyCategoryFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Category::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->word(),
        ];
    }
}
