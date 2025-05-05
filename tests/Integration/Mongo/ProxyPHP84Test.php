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
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Document\GenericDocument;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;
use Zenstruck\Foundry\Tests\Fixture\Model\GenericModel;
use Zenstruck\Foundry\Tests\Integration\Persistence\ProxyPHP84TestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresMongo;

final class ProxyPHP84Test extends ProxyPHP84TestCase
{
    use RequiresMongo;

    protected static function factory(): PersistentObjectFactory
    {
        return GenericDocumentFactory::new();
    }

    protected function dbms(): string
    {
        return 'mongo';
    }

    protected function updateObject(GenericModel $object): void
    {
        $this->documentManager()->getDocumentCollection(GenericDocument::class)
            ->updateOne(['_id' => $object->id], ['$set' => ['prop1' => 'foo']])
        ;
    }

    private function documentManager(): DocumentManager
    {
        return self::getContainer()->get(DocumentManager::class); // @phpstan-ignore return.type
    }
}
