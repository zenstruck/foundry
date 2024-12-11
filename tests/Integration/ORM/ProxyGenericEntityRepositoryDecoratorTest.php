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

use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericProxyEntityFactory;
use Zenstruck\Foundry\Tests\Integration\Persistence\GenericRepositoryDecoratorTestCase;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

final class ProxyGenericEntityRepositoryDecoratorTest extends GenericRepositoryDecoratorTestCase
{
    use RequiresORM;

    protected function factory(): GenericProxyEntityFactory // @phpstan-ignore method.childReturnType
    {
        return GenericProxyEntityFactory::new();
    }
}
