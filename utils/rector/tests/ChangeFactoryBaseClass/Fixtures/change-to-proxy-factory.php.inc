<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<DummyPersistentObject>
 *
 * @method static DummyPersistentObject[] createMany(int $number, array|callable $attributes = [])
 * @method static RepositoryProxy repository()
 * @method        DummyPersistentObject|Proxy create(array|callable $attributes = [])
 *
 * @phpstan-method Proxy&DummyPersistentObject create(array|callable $attributes = [])
 * @phpstan-method static Proxy<DummyPersistentObject> createOne(array $attributes = [])
 * @phpstan-method static DummyPersistentObject[]&Proxy[] all()
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
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<DummyPersistentObject>
 *
 * @method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject, \Doctrine\ORM\EntityRepository> repository()
 * @method \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject|Proxy create(array|callable $attributes = [])
 *
 * @phpstan-method \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject> create(array|callable $attributes = [])
 * @phpstan-method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject> createOne(array $attributes = [])
 * @phpstan-method static list<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject&Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject>> all()
 * @phpstan-method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject, \Doctrine\ORM\EntityRepository> repository()
 */
final class DummyPersistentModelFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
{
    protected function defaults(): array
    {
        return [];
    }

    protected function initialize(): static
    {
        return $this;
    }

    public static function class(): string
    {
        return DummyPersistentObject::class;
    }
}

?>
