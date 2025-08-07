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

namespace Zenstruck\Foundry\Tests\Integration\ForceFactoriesTraitUsage;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;

use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\Persistence\proxy;

#[RequiresPhpunit('>=11.0')]
#[IgnoreDeprecations]
final class ClassExtendingBaseTestCaseUsingFactoriesTest extends KernelTestCaseWithFactoriesTraitBaseTestCase
{
    #[Test]
    public function not_using_foundry_should_not_throw(): void
    {
        $this->expectNotToPerformAssertions();

        $proxyObject = proxy(factory(SomeObject::class)->create());
        $this->useProxyClass($proxyObject);
    }
}

class SomeObject
{
}
