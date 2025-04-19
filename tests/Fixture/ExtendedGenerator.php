<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Fixture;

use Faker\Generator;

/**
 * @author Silas Joisten <silasjoisten@proton.me>
 */
final class ExtendedGenerator extends Generator
{
    public function customMethod(): string
    {
        return 'custom';
    }
}
