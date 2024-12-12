<?php

use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function PHPStan\Testing\assertType;
use function Zenstruck\Foundry\Persistence\proxy;

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
assertType('UserForPersistentFactory', UserFactory::new()->create());
assertType('UserForPersistentFactory', UserFactory::createOne());
assertType('UserForPersistentFactory', UserFactory::first());
assertType('UserForPersistentFactory', UserFactory::last());
assertType('UserForPersistentFactory', UserFactory::find(1));
assertType('UserForPersistentFactory', UserFactory::random());
assertType('UserForPersistentFactory', UserFactory::findOrCreate([]));
assertType('UserForPersistentFactory', UserFactory::randomOrCreate());
assertType('UserForPersistentFactory', UserFactory::new()->instantiateWith(Instantiator::withConstructor())->create());
assertType('UserForPersistentFactory', UserFactory::new()->with()->create());

// methods returning a list of objects
assertType("array<int, UserForPersistentFactory>", UserFactory::all());
assertType("array<int, UserForPersistentFactory>", UserFactory::createMany(1));
assertType("array<int, UserForPersistentFactory>", UserFactory::createRange(1, 2));
assertType("array<int, UserForPersistentFactory>", UserFactory::createSequence([]));
assertType("array<int, UserForPersistentFactory>", UserFactory::randomRange(1, 2));
assertType("array<int, UserForPersistentFactory>", UserFactory::randomSet(2));
assertType("array<int, UserForPersistentFactory>", UserFactory::findBy(['name' => 'foo']));

// methods with FactoryCollection
$factoryCollection = FactoryCollection::class;
$factory = UserFactory::class;
assertType("{$factoryCollection}<UserForPersistentFactory, {$factory}>", UserFactory::new()->many(2));
assertType("{$factoryCollection}<UserForPersistentFactory, {$factory}>", UserFactory::new()->range(1, 2));
assertType("{$factoryCollection}<UserForPersistentFactory, {$factory}>", UserFactory::new()->sequence([]));
assertType("array<int, UserForPersistentFactory>", UserFactory::new()->many(2)->create());
assertType("array<int, UserForPersistentFactory>", UserFactory::new()->range(1, 2)->create());
assertType("array<int, UserForPersistentFactory>", UserFactory::new()->sequence([])->create());
assertType("array<int, {$factory}>", UserFactory::new()->many(2)->all());

// methods using repository()
$repository = UserFactory::repository();
assertType("Zenstruck\Foundry\Persistence\RepositoryDecorator<UserForPersistentFactory, Doctrine\Persistence\ObjectRepository<UserForPersistentFactory>>", $repository);
assertType("UserForPersistentFactory|null", $repository->first());
assertType('UserForPersistentFactory', $repository->firstOrFail());
assertType("UserForPersistentFactory|null", $repository->last());
assertType('UserForPersistentFactory', $repository->lastOrFail());
assertType("UserForPersistentFactory|null", $repository->find(1));
assertType("UserForPersistentFactory", $repository->findOrFail(1));
assertType("UserForPersistentFactory|null", $repository->findOneBy([]));
assertType('UserForPersistentFactory', $repository->random());
assertType("array<UserForPersistentFactory>", $repository->findAll());
assertType("array<UserForPersistentFactory>", $repository->findBy([]));
assertType("array<UserForPersistentFactory>", $repository->randomSet(2));
assertType("array<UserForPersistentFactory>", $repository->randomRange(1, 2));
assertType('int', $repository->count());

// test autocomplete with phpstorm
assertType('string', UserFactory::new()->create()->name);
assertType('string', UserFactory::new()->instantiateWith(Instantiator::withConstructor())->create()->name);
assertType('string', UserFactory::new()->with()->create()->name);
assertType('string', UserFactory::createOne()->name);
assertType('string', UserFactory::first()->name);
assertType('string', UserFactory::last()->name);
assertType('string', UserFactory::find(1)->name);
assertType('string', UserFactory::random()->name);
assertType('string', UserFactory::findOrCreate([])->name);
assertType('string', UserFactory::randomOrCreate()->name);

assertType('string', proxy(UserFactory::createOne())->name);
assertType('string', proxy(UserFactory::new()->create())->name);

assertType('string', UserFactory::all()[0]->name);
assertType("string", UserFactory::createMany(1)[0]->name);
assertType("string", UserFactory::createRange(1, 2)[0]->name);
assertType("string", UserFactory::createSequence([])[0]->name);
assertType("string", UserFactory::randomRange(1, 2)[0]->name);
assertType("string", UserFactory::randomSet(2)[0]->name);
assertType("string", UserFactory::findBy(['name' => 'foo'])[0]->name);

assertType("string", UserFactory::new()->many(2)->create()[0]->name);
assertType("string", UserFactory::new()->range(1, 2)->create()[0]->name);
assertType("string", UserFactory::new()->sequence([])->create()[0]->name);

assertType("string|null", $repository->first()?->name);
assertType('string', $repository->firstOrFail()->name);
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
