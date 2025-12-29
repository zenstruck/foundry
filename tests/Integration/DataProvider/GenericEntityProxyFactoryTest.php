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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericProxyEntityFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.4
 */
#[RequiresPhpunit('>=11.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[IgnoreDeprecations]
#[RequiresMethod(\Symfony\Component\VarExporter\LazyProxyTrait::class, 'createLazyProxy')]
final class GenericEntityProxyFactoryTest extends DataProviderWithPersistentFactoryInKernelTestCase
{
    use RequiresORM;

    protected static function proxyFactory(): GenericProxyEntityFactory
    {
        return GenericProxyEntityFactory::new();
    }

    protected static function factory(): PersistentObjectFactory
    {
        return GenericEntityFactory::new();
    }
}
