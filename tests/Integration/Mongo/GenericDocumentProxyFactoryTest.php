<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\Mongo;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresMethod;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Document\DocumentWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericProxyDocumentFactory;
use Zenstruck\Foundry\Tests\Integration\Persistence\GenericProxyFactoryTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresMongo;

use function Zenstruck\Foundry\Persistence\proxy_factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[IgnoreDeprecations]
#[RequiresMethod(\Symfony\Component\VarExporter\LazyProxyTrait::class, 'createLazyProxy')]
final class GenericDocumentProxyFactoryTest extends GenericProxyFactoryTestCase
{
    use RequiresMongo;

    protected static function factory(): GenericProxyDocumentFactory
    {
        return GenericProxyDocumentFactory::new();
    }

    /**
     * @return PersistentProxyObjectFactory<DocumentWithReadonly>
     */
    protected function objectWithReadonlyFactory(): PersistentProxyObjectFactory // @phpstan-ignore method.childReturnType
    {
        return proxy_factory(DocumentWithReadonly::class);
    }
}
