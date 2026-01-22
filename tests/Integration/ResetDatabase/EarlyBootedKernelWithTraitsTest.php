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

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

#[IgnoreDeprecations('In order to use Foundry correctly, you must use the trait')]
final class EarlyBootedKernelWithTraitsTest extends EarlyBootedKernelTestCase
{
    use Factories, ResetDatabase;
}
