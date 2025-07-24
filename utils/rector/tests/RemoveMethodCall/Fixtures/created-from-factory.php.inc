<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

$object = DummyPersistentProxyFactory::createOne();

$object = $object->_save()->_enableAutoRefresh();
$var = $object->_enableAutoRefresh();
$var = $object?->_enableAutoRefresh();
$object = $object->_enableAutoRefresh();
$object->_enableAutoRefresh();
$object->_save()->_enableAutoRefresh();
$object->_enableAutoRefresh()->_save();

?>
-----
<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

$object = DummyPersistentProxyFactory::createOne();

$object = $object->_save();
$var = $object;
$var = $object;
$object->_save();
$object->_save();

?>
