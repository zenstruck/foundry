<?php

use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

use function PHPStan\Testing\assertType;
use function Zenstruck\Foundry\Persistence\unproxy;

class UserForProxyFactory
{
    public function __construct(
        public string $name
    ) {}
}

/**
 * @extends PersistentProxyObjectFactory<UserForProxyFactory>
 * @phpstan-import-type Parameters from Factory
 */
final class UserProxyFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return UserForProxyFactory::class;
    }

    /** @return Parameters */
    protected function defaults(): array
    {
        return [];
    }
}

$proxyType = 'UserForProxyFactory&Zenstruck\Foundry\Persistence\Proxy<UserForProxyFactory>';

// methods returning one object
assertType($proxyType, UserProxyFactory::new()->create());
assertType($proxyType, UserProxyFactory::createOne());
assertType($proxyType, UserProxyFactory::first());
assertType($proxyType, UserProxyFactory::last());
assertType($proxyType, UserProxyFactory::find(1));
assertType($proxyType, UserProxyFactory::random());
assertType($proxyType, UserProxyFactory::findOrCreate([]));
assertType($proxyType, UserProxyFactory::randomOrCreate());
assertType($proxyType, UserProxyFactory::new()->instantiateWith(Instantiator::withConstructor())->with()->create());

// methods returning a list of objects
assertType("list<{$proxyType}>", UserProxyFactory::all());
assertType("list<{$proxyType}>", UserProxyFactory::createMany(1));
assertType("list<{$proxyType}>", UserProxyFactory::createRange(1, 2));
assertType("list<{$proxyType}>", UserProxyFactory::createSequence([]));
assertType("list<{$proxyType}>", UserProxyFactory::randomRange(1, 2));
assertType("list<{$proxyType}>", UserProxyFactory::randomSet(2));
assertType("list<{$proxyType}>", UserProxyFactory::findBy(['name' => 'foo']));

// methods with FactoryCollection
$factoryCollection = FactoryCollection::class;
$factory = UserProxyFactory::class;
assertType("{$factoryCollection}<{$proxyType}, {$factory}>", UserProxyFactory::new()->many(2));
assertType("{$factoryCollection}<{$proxyType}, {$factory}>", UserProxyFactory::new()->range(1, 2));
assertType("{$factoryCollection}<{$proxyType}, {$factory}>", UserProxyFactory::new()->sequence([]));
assertType("list<{$proxyType}>", UserProxyFactory::new()->many(2)->create());
assertType("list<{$proxyType}>", UserProxyFactory::new()->range(1, 2)->create());
assertType("list<{$proxyType}>", UserProxyFactory::new()->sequence([])->create());
assertType("list<{$factory}>", UserProxyFactory::new()->many(2)->all());

// methods using repository()
$repository = UserProxyFactory::repository();
assertType("Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<UserForProxyFactory, Doctrine\Persistence\ObjectRepository<UserForProxyFactory>>", $repository);
assertType("({$proxyType})|null", $repository->first());
assertType($proxyType, $repository->firstOrFail());
assertType("({$proxyType})|null", $repository->last());
assertType($proxyType, $repository->lastOrFail());
assertType("({$proxyType})|null", $repository->find(1));
assertType($proxyType, $repository->findOrFail(1));
assertType("({$proxyType})|null", $repository->findOneBy([]));
assertType($proxyType, $repository->random());
assertType("list<{$proxyType}>", $repository->findAll());
assertType("list<{$proxyType}>", $repository->findBy([]));
assertType("list<{$proxyType}>", $repository->randomSet(2));
assertType("list<{$proxyType}>", $repository->randomRange(1, 2));
assertType('int<0, max>', $repository->count());

// check proxy methods
assertType($proxyType, UserProxyFactory::new()->create()->_refresh());
assertType($proxyType, UserProxyFactory::createOne()->_refresh());
assertType('UserForProxyFactory', UserProxyFactory::createOne()->_real());

// test autocomplete with phpstorm
assertType('string', UserProxyFactory::new()->create()->name);
assertType('string', UserProxyFactory::new()->instantiateWith(Instantiator::withConstructor())->create()->name);
assertType('string', UserProxyFactory::new()->with()->create()->name);
assertType('string', UserProxyFactory::new()->create()->_refresh()->name);
assertType('string', UserProxyFactory::new()->create()->_real()->name); // ⚠️ no auto-complete ?!
assertType('string', UserProxyFactory::createOne()->name);
assertType('string', UserProxyFactory::createOne()->_refresh()->name);
assertType('string', UserProxyFactory::createOne()->_real()->name);
assertType('string', UserProxyFactory::first()->name);
assertType('string', UserProxyFactory::first()->_refresh()->name);
assertType('string', UserProxyFactory::last()->name);
assertType('string', UserProxyFactory::find(1)->name);
assertType('string', UserProxyFactory::find(1)->_refresh()->name);
assertType('string', UserProxyFactory::random()->name);
assertType('string', UserProxyFactory::random()->_refresh()->name);
assertType('string', UserProxyFactory::findOrCreate([])->name);
assertType('string', UserProxyFactory::findOrCreate([])->_refresh()->name);
assertType('string', UserProxyFactory::randomOrCreate()->name);
assertType('string', UserProxyFactory::randomOrCreate()->_refresh()->name);

assertType('string', unproxy(UserProxyFactory::createOne())->name);
assertType('string', unproxy(UserProxyFactory::new()->create())->name);

assertType('string', UserProxyFactory::all()[0]->name);
assertType("string", UserProxyFactory::createMany(1)[0]->name);
assertType("string", UserProxyFactory::createRange(1, 2)[0]->name);
assertType("string", UserProxyFactory::createSequence([])[0]->name);
assertType("string", UserProxyFactory::randomRange(1, 2)[0]->name);
assertType("string", UserProxyFactory::randomSet(2)[0]->name);
assertType("string", UserProxyFactory::findBy(['name' => 'foo'])[0]->name);

assertType("string", UserProxyFactory::new()->many(2)->create()[0]->name);
assertType("string", UserProxyFactory::new()->range(1, 2)->create()[0]->name);
assertType("string", UserProxyFactory::new()->sequence([])->create()[0]->name);

assertType("string|null", $repository->first()?->name);
assertType('string', $repository->firstOrFail()->name);
assertType('string', $repository->firstOrFail()->_refresh()->name);
assertType("string|null", $repository->last()?->name);
assertType('string', $repository->lastOrFail()->name);
assertType("string|null", $repository->find(1)?->name);
assertType("string", $repository->findOrFail(1)->name);
assertType("string|null", $repository->findOneBy([])?->name);
assertType('string', $repository->random()->name);
assertType("string", $repository->findAll()[0]->name);
assertType("string", $repository->findBy([])[0]->name);
assertType("string", $repository->randomSet(2)[0]->name);
assertType("string", $repository->randomRange(1, 2)[0]->name);
