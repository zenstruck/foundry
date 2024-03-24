<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<DummyObject>
 *
 * @method DummyObject|Proxy create(array|callable $attributes = [])
 */
final class DummyPersistentModelFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [];
    }

    protected function initialize(): self
    {
        return $this;
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

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<DummyObject>
 *
 * @method \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject|\Zenstruck\Foundry\Persistence\Proxy createOne(array $attributes = [])
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject|\Zenstruck\Foundry\Persistence\Proxy find(object|array|mixed $criteria)
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject|\Zenstruck\Foundry\Persistence\Proxy findOrCreate(array $attributes)
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject|\Zenstruck\Foundry\Persistence\Proxy first(string $sortedField = 'id')
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject|\Zenstruck\Foundry\Persistence\Proxy last(string $sortedField = 'id')
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject|\Zenstruck\Foundry\Persistence\Proxy random(array $attributes = [])
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject|\Zenstruck\Foundry\Persistence\Proxy randomOrCreate(array $attributes = [])
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject[]|\Zenstruck\Foundry\Persistence\Proxy[] all()
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject[]|\Zenstruck\Foundry\Persistence\Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject[]|\Zenstruck\Foundry\Persistence\Proxy[] createSequence(iterable|callable $sequence)
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject[]|\Zenstruck\Foundry\Persistence\Proxy[] findBy(array $attributes)
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject[]|\Zenstruck\Foundry\Persistence\Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject[]|\Zenstruck\Foundry\Persistence\Proxy[] randomSet(int $number, array $attributes = [])
 * @method \Zenstruck\Foundry\FactoryCollection<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject|\Zenstruck\Foundry\Persistence\Proxy> many(int $min, int|null $max)
 * @method \Zenstruck\Foundry\FactoryCollection<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject|\Zenstruck\Foundry\Persistence\Proxy> sequence(iterable|callable $sequence)
 * @method \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject, \Doctrine\ORM\EntityRepository> repository()
 *
 * @phpstan-method \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject> create(array|callable $attributes = [])
 * @phpstan-method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject> createOne(array $attributes = [])
 * @phpstan-method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject> find(object|array|mixed $criteria)
 * @phpstan-method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject> findOrCreate(array $attributes)
 * @phpstan-method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject> first(string $sortedField = 'id')
 * @phpstan-method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject> last(string $sortedField = 'id')
 * @phpstan-method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject> random(array $attributes = [])
 * @phpstan-method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject> randomOrCreate(array $attributes = [])
 * @phpstan-method static list<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject>> all()
 * @phpstan-method static list<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject>> findBy(array $attributes)
 * @phpstan-method static list<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject>> randomSet(int $number, array $attributes = [])
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject>> many(int $min, int|null $max)
 * @phpstan-method \Zenstruck\Foundry\FactoryCollection<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&\Zenstruck\Foundry\Persistence\Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject>> sequence(iterable|callable $sequence)
 */
final class DummyPersistentModelFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [];
    }

    protected function initialize(): self
    {
        return $this;
    }

    protected static function getClass(): string
    {
        return DummyPersistentObject::class;
    }
}
?>
