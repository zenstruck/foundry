<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

class SomeClass
{
    /**
     * @param Proxy<DummyObject>&DummyObject $param
     */
    public function method1($param)
    {

    }

    /**
     * @param DummyObject&Proxy<DummyObject> $param
     */
    public function method2($param)
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
     * @param \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param
     */
    public function method1(\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param)
    {

    }

    /**
     * @param \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param
     */
    public function method2(\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param)
    {

    }
}
?>
