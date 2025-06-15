<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

$object = DummyPersistentProxyFactory::createOne();

$object->_enableAutoRefresh();
$object->_save()->_enableAutoRefresh();
$object->_enableAutoRefresh()->_save();

?>
-----
<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentProxyFactory;

$object = DummyPersistentProxyFactory::createOne();
$object->_save()->_enableAutoRefresh();
$object->_enableAutoRefresh()->_save();

?>
