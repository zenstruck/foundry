<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

/**
 * @param Proxy<DummyObject> $param
 * @return Proxy<DummyObject>
 */
function function1()
{
}
?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

/**
 * @param \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param
 * @return \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
 */
function function1()
{
}
?>
