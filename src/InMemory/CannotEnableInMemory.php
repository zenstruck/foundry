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

namespace Zenstruck\Foundry\InMemory;

final class CannotEnableInMemory extends \LogicException
{
    public static function testIsNotAKernelTestCase(string $testName): self
    {
        return new self("{$testName}: Cannot use the #[AsInMemoryTest] attribute without extending KernelTestCase.");
    }

    public static function noInMemoryRepositoryRegistry(): self
    {
        return new self('Cannot enable "in memory": maybe not in a KernelTestCase?');
    }
}
