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

namespace Zenstruck\Foundry\Test;

use Faker;
use \Zenstruck\Foundry\Object\Instantiator;

final class UnitTestConfig
{
    public static function configure(?Instantiator $instantiator = null, ?Faker\Generator $faker = null): void
    {
        TestState::configureInternal($instantiator, $faker);
    }
}
