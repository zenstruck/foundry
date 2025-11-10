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

use PHPUnit\Framework\Attributes\RequiresPhpunitExtension;
use Zenstruck\Foundry\Attribute\ResetDatabase;
use Zenstruck\Foundry\PHPUnit\FoundryExtension;

#[ResetDatabase]
#[RequiresPhpunitExtension(FoundryExtension::class)]
final class GlobalStoryTest extends GlobalStoryTestCase
{
}
