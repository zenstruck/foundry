<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

final class DummyPersistentModelFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [];
    }

    public function published(): static
    {
        return $this
            ->withoutPersisting()
            ->addState([])
            ->withAttributes([]);
    }

    protected static function getClass(): string
    {
        return DummyPersistentObject::class;
    }
}

?>
-----
<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

final class DummyPersistentModelFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [];
    }

    public function published(): static
    {
        return $this
            ->withoutPersisting()
            ->with([])
            ->with([]);
    }

    protected static function getClass(): string
    {
        return DummyPersistentObject::class;
    }
}

?>
