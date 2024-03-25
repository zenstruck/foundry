<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\Persistence\Proxy;

$proxy = new \Zenstruck\Foundry\Proxy(new DummyPersistentObject());
$proxy->_real();
$proxy->object();

/**
 * @param Proxy<DummyPersistentObject> $proxy
 */
function foo(Proxy $proxy): void
{
    $proxy->_real();
    $proxy->object();
}

?>

