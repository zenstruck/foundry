<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObjectFactory;

use function Zenstruck\Foundry\Persistence\proxy;

$object = DummyObjectFactory::createOne();

\Zenstruck\Foundry\Persistence\proxy($object);
$object = \Zenstruck\Foundry\Persistence\proxy($object);
$object = proxy($object);

$other = proxy($object);
$other = proxy(DummyObjectFactory::createOne());

?>
-----
<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObjectFactory;

use function Zenstruck\Foundry\Persistence\proxy;

$object = DummyObjectFactory::createOne();

$other = $object;
$other = DummyObjectFactory::createOne();

?>
