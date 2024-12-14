<?php

use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

use function PHPStan\Testing\assertType;
use function Zenstruck\Foundry\Persistence\proxy;

class UserForObjectFactory
{
    public function __construct(
        public string $name
    ) {
    }
}

/**
 * @extends ObjectFactory<UserForObjectFactory>
 * @phpstan-import-type Parameters from Factory
 */
final class UserObjectFactory extends ObjectFactory
{
    public static function class(): string
    {
        return UserForObjectFactory::class;
    }

    /** @return Parameters */
    protected function defaults(): array
    {
        return [];
    }
}

// methods returning one object
assertType('UserForObjectFactory', UserObjectFactory::new()->create());
assertType('UserForObjectFactory', UserObjectFactory::createOne());
assertType(
    'UserForObjectFactory',
    UserObjectFactory::new()->instantiateWith(Instantiator::withConstructor())->create()
);
assertType('UserForObjectFactory', UserObjectFactory::new()->with()->create());

// methods returning a list of objects
assertType("list<UserForObjectFactory>", UserObjectFactory::createMany(1));
assertType("list<UserForObjectFactory>", UserObjectFactory::createRange(1, 2));
assertType("list<UserForObjectFactory>", UserObjectFactory::createSequence([]));

// methods with FactoryCollection
$factoryCollection = FactoryCollection::class;
$factory = UserObjectFactory::class;
assertType("{$factoryCollection}<UserForObjectFactory, {$factory}>", UserObjectFactory::new()->many(2));
assertType("{$factoryCollection}<UserForObjectFactory, {$factory}>", UserObjectFactory::new()->range(1, 2));
assertType("{$factoryCollection}<UserForObjectFactory, {$factory}>", UserObjectFactory::new()->sequence([]));
assertType("list<UserForObjectFactory>", UserObjectFactory::new()->many(2)->create());
assertType("list<UserForObjectFactory>", UserObjectFactory::new()->range(1, 2)->create());
assertType("list<UserForObjectFactory>", UserObjectFactory::new()->sequence([])->create());
assertType("list<{$factory}>", UserObjectFactory::new()->many(2)->all());

// test autocomplete with phpstorm
assertType('string', UserObjectFactory::new()->create()->name);
assertType('string', UserObjectFactory::new()->instantiateWith(Instantiator::withConstructor())->create()->name);
assertType('string', UserObjectFactory::new()->with()->create()->name);
assertType('string', UserObjectFactory::createOne()->name);

assertType("string", UserObjectFactory::createMany(1)[0]->name);
assertType("string", UserObjectFactory::createRange(1, 2)[0]->name);
assertType("string", UserObjectFactory::createSequence([])[0]->name);

assertType("string", UserObjectFactory::new()->many(2)->create()[0]->name);
assertType("string", UserObjectFactory::new()->range(1, 2)->create()[0]->name);
assertType("string", UserObjectFactory::new()->sequence([])->create()[0]->name);
