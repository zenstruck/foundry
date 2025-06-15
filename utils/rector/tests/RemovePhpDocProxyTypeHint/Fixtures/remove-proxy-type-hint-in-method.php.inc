<?php

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use App\SomeObject;
use Zenstruck\Foundry\Persistence\Proxy;

class SomeClass
{
    /**
     * @param Proxy<SomeObject> $param
     * @return Proxy<SomeObject>
     */
    public function method1()
    {
    }

    /**
     * @param SomeObject&Proxy<SomeObject> $param
     * @return SomeObject&Proxy<SomeObject>
     */
    public function method2()
    {
    }

    /**
     * @param Proxy<SomeObject>&SomeObject $param
     * @return Proxy<SomeObject>&SomeObject
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
     * @param \App\SomeObject $param
     * @return \App\SomeObject
     */
    public function method1()
    {
    }

    /**
     * @param \App\SomeObject $param
     * @return \App\SomeObject
     */
    public function method2()
    {
    }

    /**
     * @param \App\SomeObject $param
     * @return \App\SomeObject
     */
    public function method3()
    {
    }
}
?>
