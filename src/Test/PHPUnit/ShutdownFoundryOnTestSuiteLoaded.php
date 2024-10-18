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

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ShutdownFoundryOnTestSuiteLoaded implements Event\TestSuite\LoadedSubscriber
{
    public function notify(Event\TestSuite\Loaded $event): void
    {
        Configuration::shutdown();
    }
}
