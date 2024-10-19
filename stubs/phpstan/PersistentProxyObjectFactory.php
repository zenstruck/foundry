<?php

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

use function PHPStan\Testing\assertType;

class User1
{
    public string $name;
}

/**
 * The following method stubs are required for auto-completion in PhpStorm
 * AND phpstan support.
 *
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

$proxyType = 'User1&Zenstruck\Foundry\Persistence\Proxy<User1>';
assertType('User1', User1Factory::new()->create()->_real());
assertType($proxyType, User1Factory::new()->create());
assertType($proxyType, User1Factory::new()->create()->_refresh());
assertType($proxyType, User1Factory::createOne());
assertType($proxyType, User1Factory::new()->many(2)->create()[0]);
assertType($proxyType, User1Factory::new()->sequence([])->create()[0]);
assertType($proxyType, User1Factory::first());
assertType($proxyType, User1Factory::last());
assertType($proxyType, User1Factory::find(1));
assertType($proxyType, User1Factory::random());
assertType($proxyType, User1Factory::findOrCreate([]));
assertType($proxyType, User1Factory::randomOrCreate());
assertType("array<{$proxyType}>", User1Factory::all());
assertType("array<{$proxyType}>", User1Factory::createMany(1));
assertType("array<{$proxyType}>", User1Factory::createSequence([]));
assertType("array<{$proxyType}>", User1Factory::randomRange(1, 2));
assertType("array<{$proxyType}>", User1Factory::randomSet(2));
assertType("array<{$proxyType}>", User1Factory::findBy(['name' => 'foo']));
assertType("({$proxyType})|null", User1Factory::repository()->find(1));
assertType("array<{$proxyType}>", User1Factory::repository()->findAll());
assertType("Zenstruck\Foundry\Persistence\RepositoryDecorator<{$proxyType}, Doctrine\Persistence\ObjectRepository<{$proxyType}>>", User1Factory::repository());

// test autocomplete with phpstorm
assertType('string', User1Factory::new()->create()->_refresh()->name);
assertType('string', User1Factory::createOne()->_refresh()->name);
assertType('string', User1Factory::new()->many(2)->create()[0]->_refresh()->name);
assertType('string', User1Factory::createMany(1)[0]->_refresh()->name);
assertType('string', User1Factory::first()->_refresh()->name);
assertType('string', User1Factory::last()->_refresh()->name);
assertType('string', User1Factory::find(1)->_refresh()->name);
assertType('string', User1Factory::all()[0]->_refresh()->name);
assertType('string', User1Factory::random()->_refresh()->name);
assertType('string', User1Factory::randomRange(1, 2)[0]->_refresh()->name);
assertType('string', User1Factory::randomSet(2)[0]->_refresh()->name);
assertType('string', User1Factory::findBy(['name' => 'foo'])[0]->_refresh()->name);
assertType('string', User1Factory::findOrCreate([])->_refresh()->name);
assertType('string', User1Factory::randomOrCreate([])->_refresh()->name);
assertType('string|null', User1Factory::repository()->find(1)?->name);
assertType('string', User1Factory::repository()->findAll()[0]->name);
assertType('int', User1Factory::repository()->count());
