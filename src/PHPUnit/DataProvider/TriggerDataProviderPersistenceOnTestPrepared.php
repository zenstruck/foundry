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

namespace Zenstruck\Foundry\PHPUnit\DataProvider;

use PHPUnit\Event;
use Zenstruck\Foundry\Persistence\PersistentObjectFromDataProviderRegistry;

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
        /** @var Event\Code\TestMethod $test */
        if (!$test->testData()->hasDataFromDataProvider() || $test->metadata()->isDataProvider()->isEmpty()) {
            return;
        }

        PersistentObjectFromDataProviderRegistry::instance()->triggerPersistenceForDataset(
            $test->className(),
            $test->methodName(),
            $test->testData()->dataFromDataProvider()->dataSetName(),
        );
    }
}
