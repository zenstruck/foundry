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

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\Persistence\Proxy;

/**
 * @extends PersistentProxyObjectFactory<DummyPersistentObject>
 *
 * @method static ProxyRepositoryDecorator<DummyPersistentObject, EntityRepository> repository()
 * @method DummyPersistentObject|Proxy create(array|callable $attributes = [])
 *
 * @phpstan-method DummyPersistentObject&Proxy<DummyPersistentObject> create(array|callable $attributes = [])
 * @phpstan-method static DummyPersistentObject&Proxy<DummyPersistentObject> createOne(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<DummyPersistentObject, EntityRepository> repository()
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
