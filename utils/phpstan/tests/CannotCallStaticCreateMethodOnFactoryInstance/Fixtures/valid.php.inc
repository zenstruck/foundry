<?php

// static call on class name - OK
use Zenstruck\Foundry\Utils\PHPStan\Tests\Fixtures\DummyObjectFactory;

DummyObjectFactory::createOne();
DummyObjectFactory::createMany(2);
DummyObjectFactory::createSequence([]);

$class = DummyObjectFactory::class;
$class::createOne();

$inst = DummyObjectFactory::new();
$inst->create();

'test'::createOne();
$foo::createOne();

(new \stdClass())::createOne();
