<?php

namespace Zenstruck\Foundry\Tests\Benchmark;

use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeMethods;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\UnitTestConfig;

#[BeforeMethods(['_bootFoundry'])]
#[AfterMethods(['_shutdownFoundry'])]
abstract class UnitBench
{
    /**
     * @internal
     */
    public function _bootFoundry(): void
    {
        Configuration::boot(UnitTestConfig::build());
    }

    /**
     * @internal
     */
    public function _shutdownFoundry(): void
    {
        Configuration::shutdown();
    }
}
