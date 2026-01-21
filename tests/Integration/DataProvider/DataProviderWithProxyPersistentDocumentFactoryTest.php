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
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Tests\Fixture\Document\DocumentWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericProxyDocumentFactory;
use Zenstruck\Foundry\Tests\Fixture\Model\Embeddable;
use Zenstruck\Foundry\Tests\Integration\RequiresMongo;

use function Zenstruck\Foundry\Persistence\proxy_factory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.4
 */
#[RequiresPhpunit('>=11.4')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
#[IgnoreDeprecations]
#[RequiresMethod(\Symfony\Component\VarExporter\LazyProxyTrait::class, 'createLazyProxy')]
final class DataProviderWithProxyPersistentDocumentFactoryTest extends DataProviderWithPersistentFactoryTestCase
{
    use RequiresMongo;

    protected static function factory(): GenericProxyDocumentFactory
    {
        return GenericProxyDocumentFactory::new();
    }

    /**
     * @return PersistentProxyObjectFactory<DocumentWithReadonly>
     */
    protected static function objectWithReadonlyFactory(): PersistentObjectFactory
    {
        return proxy_factory(DocumentWithReadonly::class, [
            'prop' => 1,
            'embedded' => new Embeddable('value1'),
            'date' => new \DateTimeImmutable(),
        ]);
    }
}
