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

namespace Zenstruck\Foundry\PHPUnit;

use PHPUnit\Event\TestRunner\Finished;
use PHPUnit\Event\TestRunner\FinishedSubscriber;
use Zenstruck\Foundry\FakerAdapter;

final class DisplayFakerSeedOnTestSuiteFinished implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        $fakerSeed = FakerAdapter::fakerSeed();

        if (null !== $fakerSeed) {
            echo "\n\nFaker seed: ".$fakerSeed; // @phpstan-ignore ekinoBannedCode.expression
        }
    }
}
