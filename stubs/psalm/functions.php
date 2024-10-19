<?php

use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\object;
use function Zenstruck\Foundry\Persistence\persist;
use function Zenstruck\Foundry\Persistence\persistent_factory;
use function Zenstruck\Foundry\Persistence\proxy;
use function Zenstruck\Foundry\Persistence\proxy_factory;
use function Zenstruck\Foundry\Persistence\repository;

class MyUser
{
    public function __construct(
        public string $name
    ) {}
}

/** @psalm-check-type-exact $user = MyUser */
$user = factory(MyUser::class)->create();

/** @psalm-check-type-exact $user = MyUser */
$user = object(MyUser::class);

/** @psalm-check-type-exact $user = MyUser */
$user = persistent_factory(MyUser::class)->create();

/** @psalm-check-type-exact $user = MyUser */
$user = persist(MyUser::class);

/** @psalm-check-type-exact $user = MyUser|null */
$user = repository(MyUser::class)->find(1);

/** @psalm-check-type-exact $user = MyUser&Zenstruck\Foundry\Persistence\Proxy<MyUser> */
$user = proxy(object(MyUser::class));

/** @psalm-check-type-exact $name = string */
$name = proxy(object(MyUser::class))->name;

/** @psalm-check-type-exact $user = MyUser&Zenstruck\Foundry\Persistence\Proxy<MyUser> */
$user = proxy(object(MyUser::class))->_refresh();
