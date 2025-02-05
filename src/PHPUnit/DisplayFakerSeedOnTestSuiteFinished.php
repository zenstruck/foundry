<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\PHPUnit;

use PHPUnit\Event\TestRunner\Finished;
use PHPUnit\Event\TestRunner\FinishedSubscriber;
use Zenstruck\Foundry\Configuration;

final class DisplayFakerSeedOnTestSuiteFinished implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        echo "\n\nFaker seed: ". Configuration::fakerSeed(); // @phpstan-ignore ekinoBannedCode.expression
    }
}
