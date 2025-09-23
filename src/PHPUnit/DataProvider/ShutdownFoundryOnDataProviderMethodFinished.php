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
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\PHPUnit\KernelTestCaseHelper;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ShutdownFoundryOnDataProviderMethodFinished implements Event\Test\DataProviderMethodFinishedSubscriber, DataProviderSubscriberInterface
{
    public function notify(Event\Test\DataProviderMethodFinished $event): void
    {
        KernelTestCaseHelper::tearDownClass($event->testMethod()->className());

        Configuration::shutdown();
    }
}
