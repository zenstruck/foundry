<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Entity\EntityForRelations;

class EntityForRelationsFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return EntityForRelations::class;
    }

    protected function getDefaults(): array
    {
        return [];
    }
}
