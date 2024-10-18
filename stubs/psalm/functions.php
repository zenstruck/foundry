<?php

use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\object;
use function Zenstruck\Foundry\Persistence\persist;
use function Zenstruck\Foundry\Persistence\persistent_factory;
use function Zenstruck\Foundry\Persistence\proxy;
use function Zenstruck\Foundry\Persistence\repository;

class User
{
    public function __construct(
        public string $name
    ) {}
}

/** @psalm-check-type-exact $user = User */
$user = factory(User::class)->create();

/** @psalm-check-type-exact $user = User */
$user = object(User::class);

/** @psalm-check-type-exact $user = User */
$user = persistent_factory(User::class)->create();

/** @psalm-check-type-exact $user = User */
$user = persist(User::class);

/** @psalm-check-type-exact $user = User|null */
$user = repository(User::class)->find(1);

/** @psalm-check-type-exact $user = User&Zenstruck\Foundry\Persistence\Proxy<User> */
$user = proxy(object(User::class));

/** @psalm-check-type-exact $name = string */
$name = proxy(object(User::class))->name;

/** @psalm-check-type-exact $user = User&Zenstruck\Foundry\Persistence\Proxy<User> */
$user = proxy(object(User::class))->_refresh();
