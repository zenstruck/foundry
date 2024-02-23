<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Integration;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait RequiresORM
{
    public static function setUpBeforeClass(): void
    {
        if (!\getenv('DATABASE_URL')) {
            self::markTestSkipped('SQL database not available.');
        }
    }
}
