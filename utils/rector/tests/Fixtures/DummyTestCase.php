<?php

declare(strict_types=1);

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
