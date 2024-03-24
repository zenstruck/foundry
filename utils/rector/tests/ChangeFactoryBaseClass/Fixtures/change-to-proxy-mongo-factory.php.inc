<?php

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument;
use Zenstruck\Foundry\ModelFactory;

/**
 * @extends ModelFactory<DummyPersistentDocument>
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

use Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument;
use Zenstruck\Foundry\ModelFactory;

/**
 * @extends \Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory<\Zenstruck\Foundry\Utils\Rector\Tests\Fixtures\DummyPersistentDocument>
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
