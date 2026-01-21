<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\PHPUnit\DataProvider;

use PHPUnit\Event;
use PHPUnit\Event\Code\NoTestCaseObjectOnCallStackException;
use PHPUnit\Util\Test;
use Zenstruck\Foundry\Persistence\ProxyGenerator;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class TriggerDataProviderPersistenceOnTestPrepared implements Event\Test\PreparedSubscriber
{
    public function notify(Event\Test\Prepared $event): void
    {
        $test = $event->test();

        if (!$test->isTestMethod()) {
            return;
        }

        try {
            $testCase = Test::currentTestCase(); // @phpstan-ignore staticMethod.internalClass
        } catch (NoTestCaseObjectOnCallStackException) { // @phpstan-ignore catch.internalClass
            return;
        }

        $providedData = $testCase->providedData(); // @phpstan-ignore method.internal
        if ($providedData) {
            ProxyGenerator::unwrap($providedData);
        }
    }
}
