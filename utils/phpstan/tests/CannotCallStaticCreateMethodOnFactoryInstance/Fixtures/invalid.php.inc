<?php

use Zenstruck\Foundry\Utils\PHPStan\Tests\Fixtures\DummyObjectFactory;

$f = DummyObjectFactory::new();
$f::createOne();
$f::createMany(2);
$f::createRange();
$f::createSequence([]);
DummyObjectFactory::new()::createOne();
