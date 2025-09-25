<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Mongo;

use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Document\DocumentWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Document\GenericDocument;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;
use Zenstruck\Foundry\Tests\Integration\Persistence\AutoRefreshTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresMongo;
use function Zenstruck\Foundry\Persistence\persistent_factory;
use function Zenstruck\Foundry\Persistence\proxy_factory;

/**
 * @requires PHPUnit >=12
 */
#[RequiresPhpunit('>=12')]
#[RequiresEnvironmentVariable('USE_PHP_84_LAZY_OBJECTS', '1')]
#[RequiresPhp('>= 8.4')]
final class AutoRefreshTest extends AutoRefreshTestCase
{
    use RequiresMongo;

    #[Test]
    public function it_can_refresh_after_services_reset(): void
    {
        $object = $this->factory()->create();
        $objectId = $object->id;

        self::getContainer()->get('services_resetter')->reset(); // @phpstan-ignore method.notFound
        self::assertTrue((new \ReflectionClass($object))->isUninitializedLazyObject($object));

        $this->updateObject($objectId);

        self::assertSame('foo', $object->getProp1());

        self::assertTrue($this->objectManager()->contains($object));
    }

    protected static function factory(): PersistentObjectFactory
    {
        return GenericDocumentFactory::new();
    }

    protected function dbms(): string
    {
        return 'mongo';
    }

    protected function updateObject(mixed $objectId): void
    {
        $this->objectManager()->getDocumentCollection(GenericDocument::class)
            ->updateOne(['_id' => $objectId], ['$set' => ['prop1' => 'foo']])
        ;
    }

    protected function objectManager(): DocumentManager
    {
        return self::getContainer()->get(DocumentManager::class); // @phpstan-ignore return.type
    }

    /**
     * @return PersistentObjectFactory<DocumentWithReadonly>
     */
    protected function objectWithReadonlyFactory(): PersistentObjectFactory // @phpstan-ignore method.childReturnType
    {
        return persistent_factory(DocumentWithReadonly::class);
    }
}
