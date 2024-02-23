<?php

use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryDecorator;

use function PHPStan\Testing\assertType;
use function Zenstruck\Foundry\Persistence\proxy;

class User
{
    public string $name;
}

/**
 * @extends EntityRepository<User>
 */
class UserRepository extends EntityRepository
{
    public function findByName(string $name): User
    {
        return new User();
    }
}

/**
 * The following method stubs are required for auto-completion in PhpStorm
 * AND phpstan support.
 *
 * @extends PersistentObjectFactory<User>
 *
 * @method User create(array|callable $attributes = [])
 * @method static RepositoryDecorator|UserRepository repository()
 *
 * @phpstan-method static RepositoryDecorator<User,UserRepository> repository()
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
assertType('string', UserFactory::repository()->findByName('foo')->name);
assertType('int', UserFactory::repository()->count());
assertType('string', proxy(UserFactory::createOne())->name);
assertType('string', proxy(UserFactory::new()->create())->name);
