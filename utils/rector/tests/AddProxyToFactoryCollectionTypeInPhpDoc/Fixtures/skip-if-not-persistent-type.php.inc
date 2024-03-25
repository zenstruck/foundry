<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\FactoryCollection;

class Foo
{
    /**
     * @param FactoryCollection<DummyObject> $factoryCollection
     */
    public function foo(FactoryCollection $factoryCollection)
    {
    }
}

?>
