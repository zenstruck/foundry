<?php

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use App\SomeObject;
use Zenstruck\Foundry\Persistence\Proxy;

class SomeClass
{
    /**
     * @param array<Proxy<SomeObject>> $param
     * @return array<int, Proxy<SomeObject>>
     */
    public function method1()
    {
    }

    /**
     * @param list<SomeObject&Proxy<SomeObject>> $param
     * @return list<Proxy<SomeObject>&SomeObject>
     */
    public function method2()
    {
    }

    /**
     * @param array{0: Proxy<SomeObject>, 1: Proxy<SomeObject>&SomeObject} $param
     * @return array<string, Proxy<SomeObject>>
     */
    public function method3()
    {
    }
}
?>
-----
<?php

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use App\SomeObject;
use Zenstruck\Foundry\Persistence\Proxy;

class SomeClass
{
    /**
     * @param \App\SomeObject[] $param
     * @return \App\SomeObject[]
     */
    public function method1()
    {
    }

    /**
     * @param list<\App\SomeObject> $param
     * @return list<\App\SomeObject>
     */
    public function method2()
    {
    }

    /**
     * @param array{App\SomeObject, App\SomeObject} $param
     * @return array<string, \App\SomeObject>
     */
    public function method3()
    {
    }
}
?>
