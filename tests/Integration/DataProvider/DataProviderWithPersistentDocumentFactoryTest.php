<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\DataProvider;

use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Tests\Fixture\Document\DocumentWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;
use Zenstruck\Foundry\Tests\Fixture\Model\Embeddable;

use function Zenstruck\Foundry\Persistence\persistent_factory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=12
 */
#[RequiresPhp('>=8.4')]
#[RequiresPhpunit('>=12')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[RequiresEnvironmentVariable('USE_PHP_84_LAZY_OBJECTS', '1')]
#[RequiresEnvironmentVariable('MONGO_URL')]
final class DataProviderWithPersistentDocumentFactoryTest extends DataProviderWithPersistentFactoryTestCase
{
    protected static function factory(): PersistentObjectFactory
    {
        return GenericDocumentFactory::new();
    }

    /**
     * @return PersistentObjectFactory<DocumentWithReadonly>
     */
    protected static function objectWithReadonlyFactory(): PersistentObjectFactory
    {
        return persistent_factory(DocumentWithReadonly::class, [
            'prop' => 1,
            'embedded' => new Embeddable('value1'),
            'date' => new \DateTimeImmutable(),
        ]);
    }
}
