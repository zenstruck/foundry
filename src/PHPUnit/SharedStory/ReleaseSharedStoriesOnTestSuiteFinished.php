<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\PHPUnit\SharedStory;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use PHPUnit\Event;
use Zenstruck\Foundry\StoryRegistry;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ReleaseSharedStoriesOnTestSuiteFinished implements Event\TestSuite\FinishedSubscriber
{
    public function notify(Event\TestSuite\Finished $event): void
    {
        if (!StoryRegistry::releaseSharedStories($event->testSuite()->name())) {
            return;
        }

        // a test might have rolled back the whole static transaction
        if (StaticDriver::getSavepointLevel() > 0) {
            StaticDriver::rollBackAndReleaseSavepoint();
        }
    }
}
