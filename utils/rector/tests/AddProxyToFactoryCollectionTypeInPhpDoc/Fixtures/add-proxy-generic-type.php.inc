<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\FactoryCollection;

class Foo
{
    /**
     * @param FactoryCollection<DummyPersistentObject> $factoryCollection
     */
    public function foo(FactoryCollection $factoryCollection)
    {
    }
}

?>
-----
<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\FactoryCollection;

class Foo
{
    /**
     * @param FactoryCollection<\Zenstruck\Foundry\Persistence\Proxy<DummyPersistentObject>> $factoryCollection
     */
    public function foo(FactoryCollection $factoryCollection)
    {
    }
}

?>
