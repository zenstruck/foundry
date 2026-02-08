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

namespace Zenstruck\Foundry\Tests\Integration\ResetDatabase;

use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[IgnoreDeprecations('In order to use Foundry correctly, you must use the trait')]
final class ResetDatabaseWithTraitsTest extends ResetDatabaseTestCase
{
    #[BeforeClass(10)]
    public static function skipIfExtensionEnabled(): void
    {
        if (FoundryExtension::isEnabled()) {
            self::markTestSkipped('FoundryExtension is enabled.');
        }
    }
}
