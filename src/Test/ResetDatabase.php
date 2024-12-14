<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait ResetDatabase
{
    /**
     * @internal
     * @beforeClass
     */
    #[BeforeClass]
    public static function _resetDatabaseBeforeFirstTest(): void
    {
        if (!\is_subclass_of(static::class, KernelTestCase::class)) { // @phpstan-ignore function.alreadyNarrowedType
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        ResetDatabaseManager::resetBeforeFirstTest(
            static fn() => static::bootKernel(),
            static fn() => static::ensureKernelShutdown(),
        );
    }

    /**
     * @internal
     * @before
     */
    #[Before]
    public static function _resetDatabaseBeforeEachTest(): void
    {
        if (!\is_subclass_of(static::class, KernelTestCase::class)) { // @phpstan-ignore function.alreadyNarrowedType
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        ResetDatabaseManager::resetBeforeEachTest(
            static fn() => static::bootKernel(),
            static fn() => static::ensureKernelShutdown(),
        );
    }
}
