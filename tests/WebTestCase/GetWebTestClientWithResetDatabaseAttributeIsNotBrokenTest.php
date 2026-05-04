<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\WebTestCase;

use PHPUnit\Framework\Attributes\RequiresPhpunit;
use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @requires PHPUnit >=11.0.0
 */
#[ResetDatabase]
#[RequiresPhpunit('>=11.0.0')]
#[RequiresPhpunitExtension(FoundryExtension::class)]
final class GetWebTestClientWithResetDatabaseAttributeIsNotBrokenTest extends GetWebTestClientIsNotBrokenTestCase
{
}
