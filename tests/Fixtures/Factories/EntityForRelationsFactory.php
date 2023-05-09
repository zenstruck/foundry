<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\EntityForRelations;

class EntityForRelationsFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return EntityForRelations::class;
    }

    protected function getDefaults(): array
    {
        return [];
    }
}
