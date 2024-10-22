<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Entity\EdgeCases\EntityWithReadonly\EntityWithReadonly;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericProxyEntityFactory;
use Zenstruck\Foundry\Tests\Integration\Persistence\GenericProxyFactoryTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

use function Zenstruck\Foundry\Persistence\proxy_factory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class GenericEntityProxyFactoryTest extends GenericProxyFactoryTestCase
{
    use RequiresORM;

    protected function factory(): PersistentProxyObjectFactory
    {
        return GenericProxyEntityFactory::new();
    }

    /**
     * @return PersistentProxyObjectFactory<EntityWithReadonly>
     */
    protected function objectWithReadonlyFactory(): PersistentProxyObjectFactory // @phpstan-ignore method.childReturnType
    {
        return proxy_factory(EntityWithReadonly::class);
    }
}
