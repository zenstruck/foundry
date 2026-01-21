<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\ForceFactoriesTraitUsage\UsingExtension;

use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;

#[RequiresPhpunit('>=11.0')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
final class UnitTestCaseWithoutFactoriesTraitTest extends TestCase
{
    #[Test]
    public function should_not_throw(): void
    {
        $this->expectNotToPerformAssertions();

        Object1Factory::createOne();
    }
}
