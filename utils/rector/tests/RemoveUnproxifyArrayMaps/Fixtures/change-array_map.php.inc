<?php

use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObjectModelFactory;

array_map(
    static fn (Proxy $proxy) => $proxy->_real(),
    [DummyObject::new()]
);
array_map(
    fn (Proxy $proxy) => $proxy->object(),
    [DummyObject::new()]
);
array_map(
    static function (Proxy $proxy) {
        return $proxy->object();
    },
    [DummyObject::new()]
);
array_map(
    static fn (Proxy $proxy) => $proxy->object(),
    DummyObjectModelFactory::createMany(2)
);
?>
-----
<?php

use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObjectModelFactory;

[DummyObject::new()];
[DummyObject::new()];
[DummyObject::new()];
DummyObjectModelFactory::createMany(2);
?>
