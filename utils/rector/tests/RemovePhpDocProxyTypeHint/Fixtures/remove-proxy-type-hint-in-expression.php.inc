<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

/** @var Proxy<Foo> $foo */
$foo = SomeFactory::createOne();

/** @var Proxy<DummyObject> $foo */
$foo = SomeFactory::createOne();
?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;

/** @var Foo $foo */
$foo = SomeFactory::createOne();

/** @var \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $foo */
$foo = SomeFactory::createOne();
?>
