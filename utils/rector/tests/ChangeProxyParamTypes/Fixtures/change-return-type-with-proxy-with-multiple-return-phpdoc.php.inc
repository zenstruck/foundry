<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

class SomeClass
{
    /**
     * @param Proxy<DummyObject>&DummyObject $param
     * @phpstan-param Proxy<DummyObject> $param
     */
    public function someMethod(Proxy $param)
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
    public function someMethod(\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param)
    {

    }
}
?>
