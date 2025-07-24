<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObjectFactory;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

// missing Proxy type hint
array_map(
    static fn ($proxy) => $proxy->_real(),
    [DummyObject::new()]
);

// mixed type hint
array_map(
    static fn (mixed $proxy) => $proxy->_real(),
    [DummyObject::new()]
);

// param is not a proxy
array_map(
    static fn (Foo $proxy) => $proxy->_real(),
    DummyObjectFactory::createMany(2)
);

// more than "_real()" in closure
array_map(
    static fn (Proxy $proxy) => $proxy->save()->_real(),
    DummyPersistentProxyFactory::new()->many(5)->create()
);

// more than "_real()" in closure
array_map(
    static fn (Proxy $proxy) => $proxy->_real()->save(),
    DummyPersistentProxyFactory::new()->many(5)->create()
);

// not a object/_real method call
array_map(
    static fn (Proxy $proxy) => $proxy->method(),
    [DummyObject::new()]
);
?>
