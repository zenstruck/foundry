<?php

use function PHPStan\Testing\assertType;
use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\object;
use function Zenstruck\Foundry\Persistence\persist;
use function Zenstruck\Foundry\Persistence\persistent_factory;
use function Zenstruck\Foundry\Persistence\repository;

class User
{
    public string $name; // @phpstan-ignore property.uninitialized
}

assertType('string', factory(UserForPersistentFactory::class)->create()->name);
assertType('string', object(UserForPersistentFactory::class)->name);

assertType('string', persistent_factory(UserForPersistentFactory::class)->create()->name);
assertType('string', persist(UserForPersistentFactory::class)->name);

assertType('UserForPersistentFactory|null', repository(UserForPersistentFactory::class)->find(1));
