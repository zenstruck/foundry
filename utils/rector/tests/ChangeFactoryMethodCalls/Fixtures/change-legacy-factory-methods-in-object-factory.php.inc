<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

final class DummyObjectModelFactory extends ModelFactory
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
        return DummyObject::class;
    }
}

?>
-----
<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

final class DummyObjectModelFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [];
    }

    public function published(): static
    {
        return $this
            ->with([])
            ->with([]);
    }

    protected static function getClass(): string
    {
        return DummyObject::class;
    }
}

?>
