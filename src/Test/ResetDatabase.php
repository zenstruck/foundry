<?php

namespace Zenstruck\Foundry\Test;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @mixin KernelTestCase
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait ResetDatabase
{
    /**
     * @internal
     * @beforeClass
     */
    public static function _resetDatabase(): void
    {
        if (DatabaseResetter::hasBeenReset()) {
            return;
        }

        if (!\is_subclass_of(static::class, KernelTestCase::class)) {
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        static::ensureKernelShutdown();

        if ($isDAMADoctrineTestBundleEnabled = DatabaseResetter::isDAMADoctrineTestBundleEnabled()) {
            // disable static connections for this operation
            StaticDriver::setKeepStaticConnections(false);
        }

        DatabaseResetter::resetDatabase(static::bootKernel());

        if ($isDAMADoctrineTestBundleEnabled) {
            // re-enable static connections
            StaticDriver::setKeepStaticConnections(true);
        }

        static::ensureKernelShutdown();
    }

    /**
     * @internal
     * @before
     */
    public static function _resetSchema(): void
    {
        if (DatabaseResetter::isDAMADoctrineTestBundleEnabled()) {
            // not required as the DAMADoctrineTestBundle wraps each test in a transaction
            return;
        }

        if (!\is_subclass_of(static::class, KernelTestCase::class)) {
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        if (!static::$booted) {
            static::bootKernel();
        }

        DatabaseResetter::resetSchema(static::$kernel);
    }
}
