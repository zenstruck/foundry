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

use PHPUnit\Framework\Attributes\BeforeClass;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait RequiresMongo
{
    /**
     * @beforeClass
     */
    #[BeforeClass]
    public static function _requiresMongo(): void
    {
        if (!\getenv('MONGO_URL')) {
            self::markTestSkipped('MongoDB not available.');
        }
    }
}
