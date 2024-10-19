<?php

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function PHPStan\Testing\assertType;
use function Zenstruck\Foundry\Persistence\proxy;

class User
{
    public string $name;
}

/**
 * The following method stubs are required for auto-completion in PhpStorm
 * AND phpstan support.
 *
 * @extends PersistentObjectFactory<User>
 *
 * @method User create(array|callable $attributes = [])
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

assertType('User', UserFactory::new()->create());
assertType('User', UserFactory::createOne());
assertType('User', UserFactory::new()->many(2)->create()[0]);
assertType('User', UserFactory::new()->sequence([])->create()[0]);
assertType('User', UserFactory::first());
assertType('User', UserFactory::last());
assertType('User', UserFactory::find(1));
assertType('User', UserFactory::random());
assertType('User', UserFactory::findOrCreate([]));
assertType('User', UserFactory::randomOrCreate());
assertType('User|null', UserFactory::repository()->find(1));
assertType("array<User>", UserFactory::all());
assertType("array<User>", UserFactory::createMany(1));
assertType("array<User>", UserFactory::createSequence([]));
assertType("array<User>", UserFactory::randomRange(1, 2));
assertType("array<User>", UserFactory::randomSet(2));
assertType("array<User>", UserFactory::findBy(['name' => 'foo']));
assertType("array<User>", UserFactory::repository()->findAll());
assertType("Zenstruck\Foundry\Persistence\RepositoryDecorator<User, Doctrine\Persistence\ObjectRepository<User>>", UserFactory::repository());

// test autocomplete with phpstorm
assertType('string', UserFactory::new()->create()->name);
assertType('string', UserFactory::createOne()->name);
assertType('string', UserFactory::new()->many(2)->create()[0]->name);
assertType('string', UserFactory::createMany(1)[0]->name);
assertType('string', UserFactory::first()->name);
assertType('string', UserFactory::last()->name);
assertType('string', UserFactory::find(1)->name);
assertType('string', UserFactory::all()[0]->name);
assertType('string', UserFactory::random()->name);
assertType('string', UserFactory::randomRange(1, 2)[0]->name);
assertType('string', UserFactory::randomSet(2)[0]->name);
assertType('string', UserFactory::findBy(['name' => 'foo'])[0]->name);
assertType('string', UserFactory::findOrCreate([])->name);
assertType('string', UserFactory::randomOrCreate([])->name);
assertType('string|null', UserFactory::repository()->find(1)?->name);
assertType('string', UserFactory::repository()->findAll()[0]->name);
assertType('int', UserFactory::repository()->count());
assertType('string', proxy(UserFactory::createOne())->name);
assertType('string', proxy(UserFactory::new()->create())->name);
