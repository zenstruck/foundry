<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

class SomeClass
{
    /**
     * @param Proxy<DummyObject> $param
     * @return Proxy<DummyObject>
     */
    public function method1()
    {
    }

    /**
     * @param DummyObject&Proxy<DummyObject> $param
     * @return DummyObject&Proxy<DummyObject>
     */
    public function method2()
    {
    }

    /**
     * @param Proxy<DummyObject>&DummyObject $param
     * @return Proxy<DummyObject>&DummyObject
     */
    public function method3()
    {
    }

    /**
     * @param Proxy<T>&T $param
     * @return Proxy<T>&T
     */
    public function method4()
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
     * @return \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
     */
    public function method1()
    {
    }

    /**
     * @param \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param
     * @return \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
     */
    public function method2()
    {
    }

    /**
     * @param \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param
     * @return \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
     */
    public function method3()
    {
    }

    /**
     * @param T $param
     * @return T
     */
    public function method4()
    {
    }
}
?>
