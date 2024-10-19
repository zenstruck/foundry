<?php

namespace App;

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;

class User1
{
    public function __construct(
        public string $name
    ) {}
}

/**
 * @extends PersistentProxyObjectFactory<User1>
 */
final class User1Factory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return User1::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}

/** @psalm-check-type-exact $var = User1&Proxy<User1> */
$var = User1Factory::new()->create();
/** @psalm-check-type-exact $var = User1&Proxy<User1> */
$var = User1Factory::new()->create()->_refresh();
/** @psalm-check-type-exact $var = User1 */
$var = User1Factory::new()->create()->_real();
/** @psalm-check-type-exact $var = User1&Proxy<User1> */
$var = User1Factory::createOne();
/** @psalm-check-type-exact $var = User1&Proxy<User1> */
$var = User1Factory::first();
/** @psalm-check-type-exact $var = User1&Proxy<User1> */
$var = User1Factory::last();
/** @psalm-check-type-exact $var = User1&Proxy<User1> */
$var = User1Factory::find(1);
/** @psalm-check-type-exact $var = User1&Proxy<User1> */
$var = User1Factory::random();
/** @psalm-check-type-exact $var = User1&Proxy<User1> */
$var = User1Factory::findOrCreate([]);
/** @psalm-check-type-exact $var = User1&Proxy<User1> */
$var = User1Factory::randomOrCreate();
/** @psalm-check-type-exact $var = User1&Proxy<User1> */
$var = User1Factory::new()->many(2)->create()[0];
/** @psalm-check-type-exact $var = User1&Proxy<User1> */
$var = User1Factory::new()->sequence([])->create()[0];
/** @psalm-check-type-exact $var = list<User1&Proxy<User1>> */
$var = User1Factory::createMany(1);
/** @psalm-check-type-exact $var = list<User1&Proxy<User1>> */
$var = User1Factory::createSequence([]);
/** @psalm-check-type-exact $var = list<User1&Proxy<User1>> */
$var = User1Factory::all();
/** @psalm-check-type-exact $var = list<User1&Proxy<User1>> */
$var = User1Factory::randomRange(1, 2);
/** @psalm-check-type-exact $var = list<User1&Proxy<User1>> */
$var = User1Factory::randomSet(2);
/** @psalm-check-type-exact $var = list<User1&Proxy<User1>> */
$var = User1Factory::findBy(['name' => 'foo']);
/** @psalm-check-type-exact $var = (User1&Proxy<User1>)|null */
$var = User1Factory::repository()->find(1);
/** @psalm-check-type-exact $var = array<User1&Proxy<User1>> */
$var = User1Factory::repository()->findAll();
/** @psalm-check-type-exact $var = \Zenstruck\Foundry\Persistence\RepositoryDecorator<User1&Proxy<User1>, \Doctrine\Persistence\ObjectRepository<User1>> */
$var = User1Factory::repository();

/** @psalm-check-type-exact $var = string */
$var = User1Factory::new()->create()->_refresh()->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::createOne()->_refresh()->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::new()->many(2)->create()[0]->_refresh()->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::createMany(1)[0]->_refresh()->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::first()->_refresh()->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::last()->_refresh()->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::find(1)->_refresh()->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::all()[0]->_refresh()->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::random()->_refresh()->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::randomRange(1, 2)[0]->_refresh()->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::randomSet(2)[0]->_refresh()->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::findBy(['name' => 'foo'])[0]->_refresh()->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::findOrCreate([])->_refresh()->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::randomOrCreate([])->_refresh()->name;
/** @psalm-check-type-exact $var = string|null */
$var = User1Factory::repository()->find(1)?->name;
/** @psalm-check-type-exact $var = string */
$var = User1Factory::repository()->findAll()[0]->name;
/** @psalm-check-type-exact $var = int */
$var = User1Factory::repository()->count();
