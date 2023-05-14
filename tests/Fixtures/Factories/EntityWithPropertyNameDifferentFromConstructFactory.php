<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\EntityWithPropertyNameDifferentFromConstruct;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObjectFactory;

final class EntityWithPropertyNameDifferentFromConstructFactory extends PersistentObjectFactory
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

    public static function class(): string
    {
        return EntityWithPropertyNameDifferentFromConstruct::class;
    }
}
