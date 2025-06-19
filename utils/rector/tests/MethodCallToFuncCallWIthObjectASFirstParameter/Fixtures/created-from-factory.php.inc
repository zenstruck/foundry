<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

$object = DummyPersistentProxyFactory::createOne();

$object->_save();
$object = $object->_save();
$other = $object->_save();

$object->_set('property', 'value');

DummyPersistentProxyFactory::new()->create()->_set('id', 123);
?>
-----
<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

$object = DummyPersistentProxyFactory::createOne();

\Zenstruck\Foundry\Persistence\save($object);
$object = \Zenstruck\Foundry\Persistence\save($object);
$other = \Zenstruck\Foundry\Persistence\save($object);

\Zenstruck\Foundry\set($object, 'property', 'value');

\Zenstruck\Foundry\set(DummyPersistentProxyFactory::new()->create(), 'id', 123);
?>
