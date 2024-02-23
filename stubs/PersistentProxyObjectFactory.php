<?php

use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

use function PHPStan\Testing\assertType;

class User1
{
    public string $name;
}

/**
 * @extends EntityRepository<User1>
 */
class UserRepository1 extends EntityRepository
{
    public function findByName(string $name): User1
    {
        return new User1();
    }
}

/**
 * The following method stubs are required for auto-completion in PhpStorm
 * AND phpstan support.
 *
 * @extends PersistentProxyObjectFactory<User1>
 *
 * @method static RepositoryDecorator|UserRepository1 repository()
 *
 * @phpstan-method static RepositoryDecorator<User1,UserRepository1> repository()
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
assertType('string', User1Factory::repository()->findByName('foo')->name);
assertType('int', User1Factory::repository()->count());
