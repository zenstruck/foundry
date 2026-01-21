<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration\ForceFactoriesTraitUsage;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;

/**
 * @phpstan-require-extends TestCase
 */
trait SkipWithPHPUnitExtension
{
    #[Before]
    public function _skipWithPHPUnitExtension(): void
    {
        if (FoundryExtension::isEnabled()) {
            self::markTestSkipped('This test requires *NOT* using Foundry\'s PHUnit extension.');
        }
    }
}
