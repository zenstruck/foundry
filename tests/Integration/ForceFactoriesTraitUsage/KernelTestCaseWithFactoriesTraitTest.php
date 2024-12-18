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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Tests\Fixture\Factories\Object1Factory;

#[RequiresPhpunit('>=11.0')]
final class KernelTestCaseWithFactoriesTraitTest extends KernelTestCase
{
    use Factories;

    #[Test]
    public function should_not_throw(): void
    {
        Object1Factory::createOne();

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function should_not_throw_even_when_kernel_is_booted(): void
    {
        self::getContainer()->get('.zenstruck_foundry.configuration');

        Object1Factory::createOne();

        $this->expectNotToPerformAssertions();
    }
}
