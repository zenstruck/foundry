<?php

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
