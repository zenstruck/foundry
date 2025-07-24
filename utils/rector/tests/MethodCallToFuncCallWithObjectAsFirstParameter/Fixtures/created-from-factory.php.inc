<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

$object = DummyPersistentProxyFactory::createOne();

$object->_save();
$object = $object->_save();
$other = $object->_save();

$object->_get('property');

DummyPersistentProxyFactory::new()->create()->_get('id');

$object->_set('a', 'b')->_save()->_assertPersisted('message');
?>
-----
<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

$object = DummyPersistentProxyFactory::createOne();

\Zenstruck\Foundry\Persistence\save($object);
$object = \Zenstruck\Foundry\Persistence\save($object);
$other = \Zenstruck\Foundry\Persistence\save($object);

\Zenstruck\Foundry\get($object, 'property');

\Zenstruck\Foundry\get(DummyPersistentProxyFactory::new()->create(), 'id');

\Zenstruck\Foundry\Persistence\assert_persisted(\Zenstruck\Foundry\Persistence\save(\Zenstruck\Foundry\set($object, 'a', 'b')), 'message');
?>
