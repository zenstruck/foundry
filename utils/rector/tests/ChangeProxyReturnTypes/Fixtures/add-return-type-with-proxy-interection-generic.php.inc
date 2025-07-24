<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

class SomeClass
{
    /**
     * @return Proxy<DummyObject>&DummyObject
     */
    public function method1()
    {

    }

    /**
     * @return DummyObject&Proxy<DummyObject>
     */
    public function method2()
    {

    }
}
?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

class SomeClass
{
    /**
     * @return \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
     */
    public function method1(): \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
    {

    }

    /**
     * @return \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
     */
    public function method2(): \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
    {

    }
}
?>
