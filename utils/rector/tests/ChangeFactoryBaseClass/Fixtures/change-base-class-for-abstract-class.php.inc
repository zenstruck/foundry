<?php

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @template T of object
 * @extends PersistentProxyObjectFactory<T>
 */
abstract class SomeFactory extends PersistentProxyObjectFactory
{
}
?>
-----
<?php

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @template T of object
 * @extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory<T>
 */
abstract class SomeFactory extends \Zenstruck\Foundry\Persistence\PersistentObjectFactory
{
}
?>
