<?php

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function Zenstruck\Foundry\Persistence\proxy;

class User
{
    public function __construct(
        public string $name
    ) {}
}

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}

/** @psalm-check-type-exact $var = User */
$var = UserFactory::new()->create();
/** @psalm-check-type-exact $var = User */
$var = UserFactory::createOne();
/** @psalm-check-type-exact $var = User */
$var = UserFactory::new()->many(2)->create()[0];
/** @psalm-check-type-exact $var = User */
$var = UserFactory::new()->sequence([])->create()[0];
/** @psalm-check-type-exact $var = User */
$var = UserFactory::createMany(1)[0];
/** @psalm-check-type-exact $var = User */
$var = UserFactory::createSequence([])[0];
/** @psalm-check-type-exact $var = User */
$var = UserFactory::first();
/** @psalm-check-type-exact $var = User */
$var = UserFactory::last();
/** @psalm-check-type-exact $var = User */
$var = UserFactory::find(1);
/** @psalm-check-type-exact $var = array<User> */
$var = UserFactory::all();
/** @psalm-check-type-exact $var = User */
$var = UserFactory::random();
/** @psalm-check-type-exact $var = array<User> */
$var = UserFactory::randomRange(1, 2);
/** @psalm-check-type-exact $var = array<User> */
$var = UserFactory::randomSet(2);
/** @psalm-check-type-exact $var = array<User> */
$var = UserFactory::findBy(['name' => 'foo']);
/** @psalm-check-type-exact $var = User */
$var = UserFactory::findOrCreate([]);
/** @psalm-check-type-exact $var = User */
$var = UserFactory::randomOrCreate();
/** @psalm-check-type-exact $var = User|null */
$var = UserFactory::repository()->find(1);
/** @psalm-check-type-exact $var = array<User> */
$var = UserFactory::repository()->findAll();
/** @psalm-check-type-exact $var = Zenstruck\Foundry\Persistence\RepositoryDecorator<User, Doctrine\Persistence\ObjectRepository<User>> */
$var = UserFactory::repository();

// test autocomplete with phpstorm
/** @psalm-check-type-exact $var = string */
$var = UserFactory::new()->create()->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::createOne()->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::new()->many(2)->create()[0]->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::createMany(1)[0]->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::first()->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::last()->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::find(1)->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::all()[0]->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::random()->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::randomRange(1, 2)[0]->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::randomSet(2)[0]->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::findBy(['name' => 'foo'])[0]->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::findOrCreate([])->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::randomOrCreate([])->name;
/** @psalm-check-type-exact $var = string|null */
$var = UserFactory::repository()->find(1)?->name;
/** @psalm-check-type-exact $var = string */
$var = UserFactory::repository()->findAll()[0]->name;
/** @psalm-check-type-exact $var = int */
$var = UserFactory::repository()->count();
/** @psalm-check-type-exact $var = string */
$var = proxy(UserFactory::createOne())->name;
/** @psalm-check-type-exact $var = string */
$var = proxy(UserFactory::new()->create())->name;
