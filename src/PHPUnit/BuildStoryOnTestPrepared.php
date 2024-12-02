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
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class BuildStoryOnTestPrepared implements Event\Test\PreparedSubscriber
{
    public function notify(Event\Test\Prepared $event): void
    {
        $test = $event->test();

        if (!$test->isTestMethod()) {
            return;
        }
        /** @var Event\Code\TestMethod $test */

        $method = (new \ReflectionMethod($test->className(), $test->methodName()));
        $withStoryReflectionAttributes = $method->getAttributes(WithStory::class);

        if (!$withStoryReflectionAttributes) {
            return;
        }

        $withStoryAttributes = array_map(static fn(\ReflectionAttribute $a): WithStory => $a->newInstance(), $withStoryReflectionAttributes);
        foreach ($withStoryAttributes as $withStoryAttribute) {
            $withStoryAttribute->story::load();
        }
    }
}
