<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

/**
 * @return Proxy<DummyObject>
 */
function someFunction()
{

}
?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

/**
 * @return \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
 */
function someFunction(): \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
{

}
?>
