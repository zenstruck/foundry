<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

class SomeClass
{
    /**
     * @return Proxy<DummyObject>&DummyObject
     * @phpstan-return Proxy<DummyObject>
     */
    public function someMethod(): Proxy
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
    public function someMethod(): \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
    {

    }
}
?>
