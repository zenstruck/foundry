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

use PHPUnit\Event;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\InMemory\AsInMemoryTest;
use Zenstruck\Foundry\InMemory\CannotEnableInMemory;

final class EnableInMemoryBeforeTest implements Event\Test\PreparedSubscriber
{
    public function notify(Event\Test\Prepared $event): void
    {
        $test = $event->test();

        if (!$test instanceof Event\Code\TestMethod) {
            return;
        }

        $testClass = $test->className();

        if (!AsInMemoryTest::shouldEnableInMemory($testClass, $test->methodName())) {
            return;
        }

        if (!\is_subclass_of($testClass, KernelTestCase::class)) {
            throw CannotEnableInMemory::testIsNotAKernelTestCase("{$test->className()}::{$test->methodName()}");
        }

        Configuration::instance()->enableInMemory();
    }
}
