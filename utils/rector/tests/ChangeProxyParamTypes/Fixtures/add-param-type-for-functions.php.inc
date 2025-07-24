<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

/**
 * @param Proxy<DummyObject> $param1
 */
function someFunction($param1)
{

}

/**
 * @param Proxy<DummyObject> $param1
 * @param Proxy<T> $param2
 */
function someFunction2($param1, $param2)
{

}
?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

/**
 * @param \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param1
 */
function someFunction(\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param1)
{

}

/**
 * @param \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param1
 * @param T $param2
 */
function someFunction2(\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param1, object $param2)
{

}
?>
