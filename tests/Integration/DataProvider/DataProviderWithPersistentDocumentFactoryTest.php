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
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresMongo;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=12
 */
#[RequiresPhpunit('>=12')]
#[RequiresPhp('>=8.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[RequiresEnvironmentVariable('USE_PHP_84_LAZY_OBJECTS', '1')]
final class DataProviderWithPersistentDocumentFactoryTest extends DataProviderWithPersistentFactoryTestCase
{
    use RequiresMongo;

    protected static function factory(): PersistentObjectFactory
    {
        return GenericDocumentFactory::new();
    }
}
