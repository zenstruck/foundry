<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Proxy as LegacyProxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\FactoryCollection;

class Foo
{
    /**
     * @param FactoryCollection<Proxy<DummyObject>> $factoryCollection
     */
    public function foo(FactoryCollection $factoryCollection)
    {
    }

    /**
     * @param FactoryCollection<LegacyProxy<DummyObject>> $factoryCollection
     */
    public function bar(FactoryCollection $factoryCollection)
    {
    }
}

?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Proxy as LegacyProxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\FactoryCollection;

class Foo
{
    /**
     * @param FactoryCollection<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject> $factoryCollection
     */
    public function foo(FactoryCollection $factoryCollection)
    {
    }

    /**
     * @param FactoryCollection<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject> $factoryCollection
     */
    public function bar(FactoryCollection $factoryCollection)
    {
    }
}

?>
