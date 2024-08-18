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

namespace Zenstruck\Foundry\Test\PHPUnit;

use PHPUnit\Event;
use Zenstruck\Foundry\Configuration;
use function Zenstruck\Foundry\InMemory\should_enable_in_memory;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class EnableInMemoryOnDataProviderMethodCalled implements Event\Test\DataProviderMethodCalledSubscriber
{
    public function notify(Event\Test\DataProviderMethodCalled $event): void
    {
        $testMethod = $event->testMethod();

        if (!should_enable_in_memory($testMethod->className(), $testMethod->methodName())) {
            return;
        }

        Configuration::instance()->enableInMemory();
    }
}
