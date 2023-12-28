<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\Persistence\Proxy;

$proxy = new \Zenstruck\Foundry\Proxy(DummyObject::new());
$proxy->_real();
$proxy->object();

/**
 * @param Proxy<DummyObject> $proxy
 */
function foo(Proxy $proxy): void
{
    $proxy->_real();
    $proxy->object();
}

?>
-----
<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\Persistence\Proxy;

$proxy = new \Zenstruck\Foundry\Proxy(DummyObject::new());
$proxy;
$proxy;

/**
 * @param Proxy<DummyObject> $proxy
 */
function foo(Proxy $proxy): void
{
    $proxy;
    $proxy;
}

?>
