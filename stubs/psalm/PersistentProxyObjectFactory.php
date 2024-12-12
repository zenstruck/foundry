<?php

use Doctrine\Persistence\ObjectRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

class UserForProxyFactory
{
    public function __construct(
        public string $name
    ) {}
}

/**
 * @extends PersistentProxyObjectFactory<UserForProxyFactory>
 */
final class UserProxyFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return UserForProxyFactory::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}

// methods returning one object
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = UserProxyFactory::new()->create();
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = UserProxyFactory::createOne();
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = UserProxyFactory::first();
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = UserProxyFactory::last();
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = UserProxyFactory::find(1);
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = UserProxyFactory::random();
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = UserProxyFactory::findOrCreate([]);
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = UserProxyFactory::randomOrCreate();
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = UserProxyFactory::new()->instantiateWith(Instantiator::withConstructor())->create();
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = UserProxyFactory::new()->with()->create();

// methods returning a list of objects
/** @psalm-check-type-exact $var = list<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = UserProxyFactory::all();
/** @psalm-check-type-exact $var = list<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = UserProxyFactory::createMany(1);
/** @psalm-check-type-exact $var = list<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = UserProxyFactory::createRange(1, 2);
/** @psalm-check-type-exact $var = list<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = UserProxyFactory::createSequence([]);
/** @psalm-check-type-exact $var = list<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = UserProxyFactory::randomRange(1, 2);
/** @psalm-check-type-exact $var = list<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = UserProxyFactory::randomSet(2);
/** @psalm-check-type-exact $var = list<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = UserProxyFactory::findBy(['name' => 'foo']);

// methods with FactoryCollection
/** @psalm-check-type-exact $var = FactoryCollection<UserForProxyFactory&Proxy<UserForProxyFactory>, UserProxyFactory> */
$var = UserProxyFactory::new()->many(2);
/** @psalm-check-type-exact $var = FactoryCollection<UserForProxyFactory&Proxy<UserForProxyFactory>, UserProxyFactory> */
$var = UserProxyFactory::new()->range(1, 2);
/** @psalm-check-type-exact $var = FactoryCollection<UserForProxyFactory&Proxy<UserForProxyFactory>, UserProxyFactory> */
$var = UserProxyFactory::new()->sequence([]);
/** @psalm-check-type-exact $var = list<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = UserProxyFactory::new()->many(2)->create();
/** @psalm-check-type-exact $var = list<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = UserProxyFactory::new()->range(1, 2)->create();
/** @psalm-check-type-exact $var = list<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = UserProxyFactory::new()->sequence([])->create();
// not working... ?
///** @psalm-check-type-exact $var = list<UserProxyFactory> */
//$var = UserProxyFactory::new()->many(2)->all();

// methods using repository()
$repository = UserProxyFactory::repository();
/** @psalm-check-type-exact $var = ProxyRepositoryDecorator<UserForProxyFactory, ObjectRepository<UserForProxyFactory>> */
$var = $repository;
/** @psalm-check-type-exact $var = (UserForProxyFactory&Proxy<UserForProxyFactory>)|null */
$var = $repository->first();
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = $repository->firstOrFail();
/** @psalm-check-type-exact $var = (UserForProxyFactory&Proxy<UserForProxyFactory>)|null */
$var = $repository->last();
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = $repository->lastOrFail();
/** @psalm-check-type-exact $var = (UserForProxyFactory&Proxy<UserForProxyFactory>)|null */
$var = $repository->find(1);
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = $repository->findOrFail(1);
/** @psalm-check-type-exact $var = (UserForProxyFactory&Proxy<UserForProxyFactory>)|null */
$var = $repository->findOneBy([]);
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = $repository->random();
/** @psalm-check-type-exact $var = array<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = $repository->findAll();
/** @psalm-check-type-exact $var = array<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = $repository->findBy([]);
/** @psalm-check-type-exact $var = array<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = $repository->randomSet(2);
/** @psalm-check-type-exact $var = array<UserForProxyFactory&Proxy<UserForProxyFactory>> */
$var = $repository->randomRange(1, 2);
/** @psalm-check-type-exact $var = int */
$var = $repository->count();

// check proxy methods
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = UserProxyFactory::new()->create()->_refresh();
/** @psalm-check-type-exact $var = UserForProxyFactory&Proxy<UserForProxyFactory> */
$var = UserProxyFactory::createOne()->_refresh();
/** @psalm-check-type-exact $var = UserForProxyFactory */
$var = UserProxyFactory::createOne()->_real();
