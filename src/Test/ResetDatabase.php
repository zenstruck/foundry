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
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
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
        ResetDatabaseManager::resetBeforeFirstTest(
            static::_boot(...),
            static::_shutdown(...),
        );
    }

    /**
     * @internal
     * @before
     */
    #[Before]
    public static function _resetDatabaseBeforeEachTest(): void
    {
        ResetDatabaseManager::resetBeforeEachTest(
            static::_boot(...),
            static::_shutdown(...),
        );
    }

    /**
     * @internal
     */
    private static function _boot(): KernelInterface
    {
        if (!\is_subclass_of(static::class, KernelTestCase::class)) { // @phpstan-ignore function.alreadyNarrowedType
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        $kernel = static::bootKernel();

        if (!$kernel->getContainer()->has('.zenstruck_foundry.configuration')) {
            throw new \LogicException('ZenstruckFoundryBundle is not enabled. Ensure it is added to your config/bundles.php.');
        }

        Configuration::boot($kernel->getContainer()->get('.zenstruck_foundry.configuration')); // @phpstan-ignore argument.type

        return $kernel;
    }

    /**
     * @internal
     */
    private static function _shutdown(): void
    {
        if (!\is_subclass_of(static::class, KernelTestCase::class)) { // @phpstan-ignore function.alreadyNarrowedType
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        Configuration::shutdown();
        static::ensureKernelShutdown();
    }
}
