<?php

use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentModelFactory;

// missing Proxy type hint
array_map(
    static fn ($proxy) => $proxy->_real(),
    [DummyObject::new()]
);

// param is a proxy
array_map(
    static fn (Proxy $proxy) => $proxy->object(),
    DummyPersistentModelFactory::createMany(2)
);

// param is a proxy
array_map(
    static fn (Proxy $proxy) => $proxy->object(),
    DummyPersistentModelFactory::new()->many(5)->create()
);

// not a object/_real method call
array_map(
    static fn (Proxy $proxy) => $proxy->method(),
    [DummyObject::new()]
);
?>
