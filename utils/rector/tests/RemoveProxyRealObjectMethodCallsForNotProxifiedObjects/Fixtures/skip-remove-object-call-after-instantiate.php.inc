<?php

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyObject;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;

use function Zenstruck\Foundry\instantiate;

instantiate(DummyPersistentObject::class, [])->object();
instantiate(DummyObject::class, [])->object();

?>
