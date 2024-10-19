<?php

use function PHPStan\Testing\assertType;
use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\object;
use function Zenstruck\Foundry\Persistence\persist;
use function Zenstruck\Foundry\Persistence\persistent_factory;
use function Zenstruck\Foundry\Persistence\proxy;
use function Zenstruck\Foundry\Persistence\repository;

class User
{
    public string $name;
}

assertType('string', factory(User::class)->create()->name);
assertType('string', object(User::class)->name);

assertType('string', persistent_factory(User::class)->create()->name);
assertType('string', persist(User::class)->name);

assertType('User|null', repository(User::class)->find(1));

assertType('User&Zenstruck\Foundry\Persistence\Proxy<User>', proxy(object(User::class)));
assertType('string', proxy(object(User::class))->name);
assertType('string', proxy(object(User::class))->_refresh()->name);
