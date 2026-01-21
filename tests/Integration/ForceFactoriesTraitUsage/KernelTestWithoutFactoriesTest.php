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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[RequiresPhpunit('>=11.0')]
final class KernelTestWithoutFactoriesTest extends KernelTestCase
{
    use KernelTestCaseWithoutFactoriesTrait, SkipWithPHPUnitExtension;
}
