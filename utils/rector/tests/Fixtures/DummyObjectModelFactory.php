<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\ModelFactory;

final class DummyObjectModelFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [];
    }

    protected static function getClass(): string
    {
        return DummyObject::class;
    }
}
