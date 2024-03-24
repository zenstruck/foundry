<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<DummyPersistentObject>
 *
 * @method static RepositoryProxy repository()
 * @method        DummyPersistentObject|Proxy create(array|callable $attributes = [])
 *
 * @phpstan-method Proxy<DummyPersistentObject> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<DummyPersistentObject> createOne(array $attributes = [])
 * @phpstan-method static RepositoryProxy<DummyPersistentObject> repository()
 */
final class DummyPersistentModelFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [];
    }

    protected function initialize(): self
    {
        return $this
            ->withoutPersisting();
    }

    protected static function getClass(): string
    {
        return DummyPersistentObject::class;
    }
}

?>
-----
<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 *
 *
 * @method DummyPersistentObject|Proxy create(array|callable $attributes = [])
 * @method static DummyPersistentObject|Proxy createOne(array $attributes = [])
 * @method static DummyPersistentObject|Proxy find(object|array|mixed $criteria)
 * @method static DummyPersistentObject|Proxy findOrCreate(array $attributes)
 * @method static DummyPersistentObject|Proxy first(string $sortedField = 'id')
 * @method static DummyPersistentObject|Proxy last(string $sortedField = 'id')
 * @method static DummyPersistentObject|Proxy random(array $attributes = [])
 * @method static DummyPersistentObject|Proxy randomOrCreate(array $attributes = [])
 * @method static DummyPersistentObject[]|Proxy[] all()
 * @method static DummyPersistentObject[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static DummyPersistentObject[]|Proxy[] createSequence(iterable|callable $sequence)
 * @method static DummyPersistentObject[]|Proxy[] findBy(array $attributes)
 * @method static DummyPersistentObject[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static DummyPersistentObject[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method FactoryCollection<DummyPersistentObject|Proxy> many(int $min, int|null $max)
 * @method FactoryCollection<DummyPersistentObject|Proxy> sequence(iterable|callable $sequence)
 * @method ProxyRepositoryDecorator<DummyPersistentObject, EntityRepository> repository()
 *
 * @phpstan-method DummyPersistentObject&Proxy<DummyPersistentObject> create(array|callable $attributes = [])
 * @phpstan-method static DummyPersistentObject&Proxy<DummyPersistentObject> createOne(array $attributes = [])
 * @phpstan-method static DummyPersistentObject&Proxy<DummyPersistentObject> find(object|array|mixed $criteria)
 * @phpstan-method static DummyPersistentObject&Proxy<DummyPersistentObject> findOrCreate(array $attributes)
 * @phpstan-method static DummyPersistentObject&Proxy<DummyPersistentObject> first(string $sortedField = 'id')
 * @phpstan-method static DummyPersistentObject&Proxy<DummyPersistentObject> last(string $sortedField = 'id')
 * @phpstan-method static DummyPersistentObject&Proxy<DummyPersistentObject> random(array $attributes = [])
 * @phpstan-method static DummyPersistentObject&Proxy<DummyPersistentObject> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<DummyPersistentObject&Proxy<DummyPersistentObject>> all()
 * @phpstan-method static list<DummyPersistentObject&Proxy<DummyPersistentObject>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<DummyPersistentObject&Proxy<DummyPersistentObject>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<DummyPersistentObject&Proxy<DummyPersistentObject>> findBy(array $attributes)
 * @phpstan-method static list<DummyPersistentObject&Proxy<DummyPersistentObject>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<DummyPersistentObject&Proxy<DummyPersistentObject>> randomSet(int $number, array $attributes = [])
 * @phpstan-method FactoryCollection<DummyPersistentObject&Proxy<DummyPersistentObject>> many(int $min, int|null $max)
 * @phpstan-method FactoryCollection<DummyPersistentObject&Proxy<DummyPersistentObject>> sequence(iterable|callable $sequence)
 * @extends PersistentProxyObjectFactory<DummyPersistentObject>
 */
final class DummyPersistentModelFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array
    {
        return [];
    }

    protected function initialize(): static
    {
        return $this
            ->withoutPersisting();
    }

    public static function class(): string
    {
        return DummyPersistentObject::class;
    }
}

?>
