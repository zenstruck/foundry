<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

class SomeClass
{
    /**
     * @param DummyObject $param
     */
    public function method1($param)
    {

    }

    /**
     * @param Proxy $param
     */
    public function method2($param)
    {

    }

    public function method3()
    {

    }
}
?>
