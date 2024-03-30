<?php

namespace Zenstruck\Foundry\Tests\Fixtures\Factories;

use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Tests\Fixtures\Object\SomeObject;

/**
 * @author Jesse Rushlow <jr@rushlow.dev>
 */
class LegacyObjectFactory extends ModelFactory
{
    protected static function getClass(): string
    {
        return SomeObject::class;
    }

    protected function getDefaults(): array
    {
        return [
            'propertyWithoutType' => self::faker()->word(),
        ];
    }

    protected function initialize(): self
    {
        return $this->withoutPersisting();
    }
}
