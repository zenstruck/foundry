<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Document\ODMCategory;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ODMAnonymousFactoryTest extends AnonymousFactoryTest
{
    protected function setUp(): void
    {
        if (!\getenv('USE_ODM')) {
            self::markTestSkipped('doctrine/odm not enabled.');
        }
    }

    protected function categoryClass(): string
    {
        return ODMCategory::class;
    }
}
