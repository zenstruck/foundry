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
use Zenstruck\Foundry\Persistence\PersistenceManager;

use function Zenstruck\Foundry\restorePhpUnitErrorHandler;

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
    public static function _resetDatabase(): void
    {
        if (!\is_subclass_of(static::class, KernelTestCase::class)) {
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        PersistenceManager::resetDatabase(
            static fn() => static::bootKernel(),
            static function(): void {
                static::ensureKernelShutdown();
                restorePhpUnitErrorHandler();
            },
        );
    }

    /**
     * @internal
     * @before
     */
    #[Before]
    public static function _resetSchema(): void
    {
        if (!\is_subclass_of(static::class, KernelTestCase::class)) {
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        PersistenceManager::resetSchema(
            static fn() => static::bootKernel(),
            static fn() => static::ensureKernelShutdown(),
        );
    }
}
