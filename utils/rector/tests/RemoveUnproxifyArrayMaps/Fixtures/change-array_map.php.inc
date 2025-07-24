<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

$objects = array_map(
    static fn (Proxy $proxy) => $proxy->_real(),
    [DummyObject::new()]
);
array_map(
    fn (Proxy $proxy) => $proxy->_real(),
    [DummyObject::new()]
);
array_map(
    static function (Proxy $proxy) {
        return $proxy->_real();
    },
    [DummyObject::new()]
);
array_map(
    static fn (Proxy $proxy) => $proxy->_real(),
    DummyPersistentProxyFactory::createMany(2)
);
array_map(
    static fn (?Proxy $proxy) => $proxy?->_real(),
    DummyPersistentProxyFactory::createMany(2)
);
?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

$objects = [DummyObject::new()];
[DummyObject::new()];
[DummyObject::new()];
DummyPersistentProxyFactory::createMany(2);
DummyPersistentProxyFactory::createMany(2);
?>
