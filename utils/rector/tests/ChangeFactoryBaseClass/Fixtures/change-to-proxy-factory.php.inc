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
 * @method static RepositoryProxy|EntityRepository repository()
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

use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentObject;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<DummyPersistentObject>
 *
 * @method static EntityRepository|\Zenstruck\Foundry\Persistence\RepositoryDecorator repository()
 * @method static \Zenstruck\Foundry\Persistence\RepositoryDecorator repository()
 * @method        DummyPersistentObject|Proxy create(array|callable $attributes = [])
 *
 * @phpstan-method Proxy<DummyPersistentObject> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<DummyPersistentObject> createOne(array $attributes = [])
 * @phpstan-method static \Zenstruck\Foundry\Persistence\RepositoryDecorator<DummyPersistentObject> repository()
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
