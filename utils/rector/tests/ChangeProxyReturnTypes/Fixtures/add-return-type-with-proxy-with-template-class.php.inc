<?php

use Zenstruck\Foundry\Persistence\Proxy;

/**
 * @template T of object
 */
class SomeClass
{
    /**
     * @return Proxy<T>
     */
    public function method1()
    {

    }

    /**
     * @return Proxy<T>
     */
    public function method2(): Proxy
    {

    }
}
?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;

/**
 * @template T of object
 */
class SomeClass
{
    /**
     * @return T
     */
    public function method1(): object
    {

    }

    /**
     * @return T
     */
    public function method2(): object
    {

    }
}
?>
