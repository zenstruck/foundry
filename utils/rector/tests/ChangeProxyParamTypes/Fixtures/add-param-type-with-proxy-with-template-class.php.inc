<?php

use Zenstruck\Foundry\Persistence\Proxy;

/**
 * @template T of object
 */
class SomeClass
{
    /**
     * @param Proxy<T> $param
     */
    public function method1($param)
    {

    }

    /**
     * @param Proxy<T> $param
     */
    public function method2(Proxy $param)
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
     * @param T $param
     */
    public function method1(object $param)
    {

    }

    /**
     * @param T $param
     */
    public function method2(object $param)
    {

    }
}
?>
