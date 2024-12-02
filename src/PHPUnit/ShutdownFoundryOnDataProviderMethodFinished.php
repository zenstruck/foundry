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

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ShutdownFoundryOnDataProviderMethodFinished implements Event\Test\DataProviderMethodFinishedSubscriber
{
    public function notify(Event\Test\DataProviderMethodFinished $event): void
    {
        if (\method_exists($event->testMethod()->className(), '_shutdownAfterDataProvider')) {
            $event->testMethod()->className()::_shutdownAfterDataProvider();
        }
    }
}
