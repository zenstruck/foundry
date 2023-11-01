<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<DummyPersistentDocument>
 *
 * @method static RepositoryProxy repository()
 * @method        DummyPersistentDocument|Proxy create(array|callable $attributes = [])
 *
 * @phpstan-method Proxy&DummyPersistentDocument create(array|callable $attributes = [])
 * @phpstan-method static Proxy<DummyPersistentDocument> createOne(array $attributes = [])
 * @phpstan-method static DummyPersistentDocument[]&Proxy[] all()
 * @phpstan-method static DummyPersistentDocument[]&Proxy[] createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static RepositoryProxy<DummyPersistentDocument> repository()
 */
final class DummyPersistentDocumentFactory extends ModelFactory
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
        return DummyPersistentDocument::class;
    }
}

?>
-----
<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<DummyPersistentDocument>
 *
 * @method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument, \Doctrine\ODM\MongoDB\Repository\DocumentRepository> repository()
 * @method \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument|Proxy create(array|callable $attributes = [])
 *
 * @phpstan-method \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument&Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument> create(array|callable $attributes = [])
 * @phpstan-method static \Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument&Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument> createOne(array $attributes = [])
 * @phpstan-method static list<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument&Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument>> all()
 * @phpstan-method static list<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument&Proxy<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static \Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument, \Doctrine\ODM\MongoDB\Repository\DocumentRepository> repository()
 */
final class DummyPersistentDocumentFactory extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory
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
        return DummyPersistentDocument::class;
    }
}

?>
