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
use Zenstruck\Foundry\Tests\Fixtures\Entity\EntityWithPropertyNameDifferentFromConstruct;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObjectFactory;

final class EntityWithPropertyNameDifferentFromConstructFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'scalar' => self::faker()->name(),
            'relationship' => EntityForRelationsFactory::new(),
            'embedded' => AddressFactory::new(),
            'notPersistedObject' => SomeObjectFactory::new(),
        ];
    }

    public static function class(): string
    {
        return EntityWithPropertyNameDifferentFromConstruct::class;
    }
}
