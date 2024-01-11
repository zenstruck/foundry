<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

final class DummyModelFactoryExtendingUserFactory extends DummyObjectModelFactory
{
    protected function getDefaults(): array
    {
        return self::getDefaults();
    }

    protected function initialize(): self
    {
        return $this;
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

final class DummyModelFactoryExtendingUserFactory extends DummyObjectModelFactory
{
    protected function getDefaults(): array
    {
        return self::defaults();
    }

    protected function initialize(): self
    {
        return $this;
    }

    protected static function getClass(): string
    {
        return DummyObject::class;
    }
}
?>
