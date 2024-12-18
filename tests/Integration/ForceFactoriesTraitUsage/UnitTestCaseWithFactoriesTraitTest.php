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

use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;

#[RequiresPhpunit('>=11.0')]
final class UnitTestCaseWithFactoriesTraitTest extends TestCase
{
    use Factories;

    #[Test]
    public function should_not_throw(): void
    {
        Object1Factory::createOne();

        $this->expectNotToPerformAssertions();
    }
}
