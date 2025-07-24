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

namespace Zenstruck\Foundry\Utils\Rector\Tests\Fixtures;

use PHPUnit\Framework\TestCase;

/**
 * This is a totally dummy file, which is only helpful for autoloader:
 * when this file exist, this class exists, and fixtures files using same class name will be considered as existing
 *
 * @see tests/Rector/ChangeDisableEnablePersist/Fixtures/change-legacy-function-when-in-kernel-test-case.php.inc
 */
class DummyTestCase extends TestCase
{
}
