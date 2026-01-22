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
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Document\DocumentWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Document\GenericDocument;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;
use Zenstruck\Foundry\Tests\Integration\Persistence\AutoRefreshTestCase;

use function Zenstruck\Foundry\Persistence\persistent_factory;

/**
 * @requires PHPUnit >=12
 */
#[RequiresPhp('>= 8.4')]
#[RequiresPhpunit('>=12')]
#[RequiresEnvironmentVariable('USE_PHP_84_LAZY_OBJECTS', '1')]
#[RequiresEnvironmentVariable('MONGO_URL')]
final class AutoRefreshTest extends AutoRefreshTestCase
{
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
