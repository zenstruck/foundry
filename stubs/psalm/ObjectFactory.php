<?php

use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\ObjectFactory;

use function PHPStan\Testing\assertType;

class UserForObjectFactory
{
    public function __construct(
        public string $name
    ) {
    }
}

/**
 * @extends ObjectFactory<UserForObjectFactory>
 */
final class UserObjectFactory extends ObjectFactory
{
    public static function class(): string
    {
        return UserForObjectFactory::class;
    }

    protected function defaults(): array|callable
    {
        return [];
    }
}

// methods returning one object
/** @psalm-check-type-exact $var = UserForObjectFactory */
$var = UserObjectFactory::new()->create();
/** @psalm-check-type-exact $var = UserForObjectFactory */
$var = UserObjectFactory::createOne();
/** @psalm-check-type-exact $var = UserForObjectFactory */
$var = UserObjectFactory::new()->instantiateWith(Instantiator::withConstructor())->create();
/** @psalm-check-type-exact $var = UserForObjectFactory */
$var = UserObjectFactory::new()->with()->create();

// methods returning a list of objects
/** @psalm-check-type-exact $var = list<UserForObjectFactory> */
$var = UserObjectFactory::createMany(1);
/** @psalm-check-type-exact $var = list<UserForObjectFactory> */
$var = UserObjectFactory::createRange(1, 2);
/** @psalm-check-type-exact $var = list<UserForObjectFactory> */
$var = UserObjectFactory::createSequence([]);

// methods with FactoryCollection
/** @psalm-check-type-exact $var = FactoryCollection<UserForObjectFactory, UserObjectFactory> */
$var = UserObjectFactory::new()->many(2);
/** @psalm-check-type-exact $var = FactoryCollection<UserForObjectFactory, UserObjectFactory> */
$var = UserObjectFactory::new()->range(1, 2);
/** @psalm-check-type-exact $var = FactoryCollection<UserForObjectFactory, UserObjectFactory> */
$var = UserObjectFactory::new()->sequence([]);
/** @psalm-check-type-exact $var = list<UserForObjectFactory> */
$var = UserObjectFactory::new()->many(2)->create();
/** @psalm-check-type-exact $var = list<UserForObjectFactory> */
$var = UserObjectFactory::new()->range(1, 2)->create();
/** @psalm-check-type-exact $var = list<UserForObjectFactory> */
$var = UserObjectFactory::new()->sequence([])->create();
/** @psalm-check-type-exact $var = list<UserObjectFactory> */
$var = UserObjectFactory::new()->many(2)->all();
