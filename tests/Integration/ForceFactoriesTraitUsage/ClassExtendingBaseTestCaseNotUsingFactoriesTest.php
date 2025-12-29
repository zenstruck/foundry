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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;

use function Zenstruck\Foundry\factory;
use function Zenstruck\Foundry\Persistence\proxy;

#[RequiresPhpunit('>=11.0')]
final class ClassExtendingBaseTestCaseNotUsingFactoriesTest extends AnotherBaseTestCase
{
    use KernelTestCaseWithoutFactoriesTrait;

    #[Test]
    #[IgnoreDeprecations]
    #[RequiresMethod(\Symfony\Component\VarExporter\LazyProxyTrait::class, 'createLazyProxy')]
    public function using_foundry_should_trigger_deprecation(): void
    {
        $this->assertDeprecation();

        $proxyObject = proxy(factory(SomeObject2::class)->create());
        $this->useProxyClass($proxyObject);
    }
}

class SomeObject2
{
}
