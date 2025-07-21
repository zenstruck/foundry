<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

/**
 * @method Proxy<DummyObject> someMethod()
 */
class SomeClass
{
}
?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

/**
 * @method \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject someMethod()
 */
class SomeClass
{
}
?>
