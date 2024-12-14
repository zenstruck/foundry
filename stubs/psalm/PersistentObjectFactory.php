<?php

use Doctrine\Persistence\ObjectRepository;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use Zenstruck\Foundry\Persistence\RepositoryDecorator;

class UserForPersistentFactory
{
    public function __construct(
        public string $name
    ) {}
}

/**
 * @extends PersistentObjectFactory<UserForPersistentFactory>
 */
final class UserFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return UserForPersistentFactory::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}

// methods returning one object
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = UserFactory::new()->create();
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = UserFactory::createOne();
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = UserFactory::first();
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = UserFactory::last();
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = UserFactory::find(1);
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = UserFactory::random();
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = UserFactory::findOrCreate([]);
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = UserFactory::randomOrCreate();
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = UserFactory::new()->instantiateWith(Instantiator::withConstructor())->create();
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = UserFactory::new()->with()->create();

// methods returning a list of objects
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = UserFactory::all();
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = UserFactory::createMany(1);
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = UserFactory::createRange(1, 2);
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = UserFactory::createSequence([]);
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = UserFactory::randomRange(1, 2);
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = UserFactory::randomSet(2);
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = UserFactory::findBy(['name' => 'foo']);

// methods with FactoryCollection
/** @psalm-check-type-exact $var = FactoryCollection<UserForPersistentFactory, UserFactory> */
$var = UserFactory::new()->many(2);
/** @psalm-check-type-exact $var = FactoryCollection<UserForPersistentFactory, UserFactory> */
$var = UserFactory::new()->range(1, 2);
/** @psalm-check-type-exact $var = FactoryCollection<UserForPersistentFactory, UserFactory> */
$var = UserFactory::new()->sequence([]);
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = UserFactory::new()->many(2)->create();
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = UserFactory::new()->range(1, 2)->create();
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = UserFactory::new()->sequence([])->create();
/** @psalm-check-type-exact $var = list<UserFactory> */
$var = UserFactory::new()->many(2)->all();

// methods using repository()
$repository = UserFactory::repository();
/** @psalm-check-type-exact $var = RepositoryDecorator<UserForPersistentFactory, ObjectRepository<UserForPersistentFactory>> */
$var = $repository;
/** @psalm-check-type-exact $var = UserForPersistentFactory|null */
$var = $repository->first();
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = $repository->firstOrFail();
/** @psalm-check-type-exact $var = UserForPersistentFactory|null */
$var = $repository->last();
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = $repository->lastOrFail();
/** @psalm-check-type-exact $var = UserForPersistentFactory|null */
$var = $repository->find(1);
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = $repository->findOrFail(1);
/** @psalm-check-type-exact $var = UserForPersistentFactory|null */
$var = $repository->findOneBy([]);
/** @psalm-check-type-exact $var = UserForPersistentFactory */
$var = $repository->random();
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = $repository->findAll();
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = $repository->findBy([]);
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = $repository->randomSet(2);
/** @psalm-check-type-exact $var = list<UserForPersistentFactory> */
$var = $repository->randomRange(1, 2);
/** @psalm-check-type-exact $var = int */
$var = $repository->count();
