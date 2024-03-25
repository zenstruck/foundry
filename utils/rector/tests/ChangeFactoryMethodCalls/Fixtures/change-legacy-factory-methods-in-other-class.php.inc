<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

final class SomeClass
{
    protected function someMethod()
    {
        DummyObjectModelFactory::new()->withoutPersisting()->withAttributes([]);
        DummyPersistentModelFactory::new()->withoutPersisting()->withAttributes([]);
    }
}

?>
-----
<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

final class SomeClass
{
    protected function someMethod()
    {
        DummyObjectModelFactory::new()->with([]);
        DummyPersistentModelFactory::new()->withoutPersisting()->with([]);
    }
}

?>
