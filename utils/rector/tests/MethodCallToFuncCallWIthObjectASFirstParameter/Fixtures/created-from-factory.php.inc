<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

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

?>
-----
<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

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

?>
