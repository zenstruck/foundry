<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObjectFactory;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;
use App\SomeObject;

use function Zenstruck\Foundry\Persistence\proxy;
use function Zenstruck\Foundry\Persistence\unproxy;

$object = DummyPersistentProxyFactory::createOne();

$object->_save();
$object = $object->_save();
$other = $object->_save();

$object->_refresh();

$object->_delete();

$prop = $object->_get('property');
$object->_set('property', 'value');

$object->_assertPersisted();
$object->_assertPersisted('with message');

$object->_assertNotPersisted();
$object->_assertNotPersisted('with message');

$object = unproxy($object);

$object = DummyObjectFactory::createOne();
$proxy = proxy($object);
$proxy->_enableAutoRefresh();
$proxy->_save();
$proxy = $proxy->_real();
$object = $proxy->_real();

/**
 * @param Proxy<DummyObject> $param
 * @return Proxy<DummyObject>
 */
function function1()
{
}

/**
 * @param Proxy<DummyObject> $param
 * @return Proxy<DummyObject>
 */
function functionWithReturnChange(Proxy $param)
{

}

/**
 * @return Proxy<DummyObject>&DummyObject
 * @phpstan-return Proxy<DummyObject>
 */
function functionWithReturnChange2(): Proxy
{

}

array_map(
    fn (Proxy $proxy) => $proxy->_real(),
    [DummyObject::new()]
);
?>
-----
<?php

use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObjectFactory;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;
use App\SomeObject;

use function Zenstruck\Foundry\Persistence\proxy;
use function Zenstruck\Foundry\Persistence\unproxy;

$object = DummyPersistentProxyFactory::createOne();

\Zenstruck\Foundry\Persistence\save($object);
$object = \Zenstruck\Foundry\Persistence\save($object);
$other = \Zenstruck\Foundry\Persistence\save($object);

\Zenstruck\Foundry\Persistence\refresh($object);

\Zenstruck\Foundry\Persistence\delete($object);

$prop = \Zenstruck\Foundry\get($object, 'property');
\Zenstruck\Foundry\set($object, 'property', 'value');

\Zenstruck\Foundry\Persistence\assert_persisted($object);
\Zenstruck\Foundry\Persistence\assert_persisted($object, 'with message');

\Zenstruck\Foundry\Persistence\assert_not_persisted($object);
\Zenstruck\Foundry\Persistence\assert_not_persisted($object, 'with message');

$object = DummyObjectFactory::createOne();
$proxy = $object;
\Zenstruck\Foundry\Persistence\save($proxy);
$object = $proxy;

/**
 * @param \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param
 * @return \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
 */
function function1(): \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
{
}

/**
 * @param \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param
 * @return \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
 */
function functionWithReturnChange(\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject $param): \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
{

}

/**
 * @return \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
 */
function functionWithReturnChange2(): \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject
{

}

[DummyObject::new()];
?>
