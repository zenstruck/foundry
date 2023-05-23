<?php

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
