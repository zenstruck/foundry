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

namespace Zenstruck\Foundry\Tests\Integration\ORM;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericProxyEntityFactory;

/**
 * @group legacy
 */
#[IgnoreDeprecations]
#[Group('legacy-proxy')]
final class ProxyGenericEntityRepositoryDecoratorTest extends GenericEntityRepositoryDecoratorTest
{
    protected function factory(): PersistentObjectFactory
    {
        return GenericProxyEntityFactory::new(); // @phpstan-ignore return.type
    }
}
