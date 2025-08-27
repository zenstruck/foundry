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

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericDocumentFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericProxyDocumentFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresMongo;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.4
 */
#[RequiresPhpunit('>=11.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[IgnoreDeprecations]
final class GenericDocumentProxyFactoryTest extends DataProviderWithPersistentFactoryInKernelTestCase
{
    use RequiresMongo;

    protected static function proxyFactory(): GenericProxyDocumentFactory
    {
        return GenericProxyDocumentFactory::new();
    }

    protected static function factory(): PersistentObjectFactory
    {
        return GenericDocumentFactory::new();
    }
}
