<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

class SomeClass
{
    /**
     * @param array<Proxy<DummyObject>> $param
     * @return array<int, Proxy<DummyObject>>
     */
    public function method1()
    {
    }

    /**
     * @param list<DummyObject&Proxy<DummyObject>> $param
     * @return list<Proxy<DummyObject>&DummyObject>
     */
    public function method2()
    {
    }

    /**
     * @param array{0: Proxy<DummyObject>, 1: Proxy<DummyObject>&DummyObject} $param
     * @return array<string, Proxy<DummyObject>>
     */
    public function method3()
    {
    }

    /**
     * @param array<Proxy<T>> $param
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
     * @param \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject[] $param
     * @return \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject[]
     */
    public function method1()
    {
    }

    /**
     * @param list<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject> $param
     * @return list<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject>
     */
    public function method2()
    {
    }

    /**
     * @param array{Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject, Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject} $param
     * @return array<string, \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject>
     */
    public function method3()
    {
    }

    /**
     * @param T[] $param
     */
    public function method4()
    {
    }
}
?>
