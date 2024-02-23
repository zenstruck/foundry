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

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Document\GenericProxyDocumentFactory;
use Zenstruck\Foundry\Tests\Integration\Persistence\GenericProxyFactoryTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresMongo;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class GenericDocumentProxyFactoryTest extends GenericProxyFactoryTestCase
{
    use RequiresMongo;

    protected function factory(): PersistentProxyObjectFactory
    {
        return GenericProxyDocumentFactory::new();
    }
}
