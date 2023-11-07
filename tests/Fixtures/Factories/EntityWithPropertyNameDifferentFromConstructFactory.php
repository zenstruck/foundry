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

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\EntityWithPropertyNameDifferentFromConstruct;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObjectFactory;

final class EntityWithPropertyNameDifferentFromConstructFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'scalar' => self::faker()->name(),
            'relationship' => EntityForRelationsFactory::new(),
            'embedded' => AddressFactory::new(),
            'notPersistedObject' => SomeObjectFactory::new(),
        ];
    }

    protected static function getClass(): string
    {
        return EntityWithPropertyNameDifferentFromConstruct::class;
    }
}
