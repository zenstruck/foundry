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

use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericProxyDocumentFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresMongo;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.4
 */
#[RequiresPhpunit('>=11.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
final class GenericDocumentProxyFactoryTest extends DataProviderWithProxyFactoryInKernelTestCase
{
    use RequiresMongo;

    protected static function factory(): GenericProxyDocumentFactory
    {
        return GenericProxyDocumentFactory::new();
    }
}
