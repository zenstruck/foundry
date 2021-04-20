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

        if ($isDAMADoctrineTestBundleEnabled = DatabaseResetter::isDAMADoctrineTestBundleEnabled()) {
            // disable static connections for this operation
            StaticDriver::setKeepStaticConnections(false);
        }

        $kernel = static::createKernel();
        $kernel->boot();

        DatabaseResetter::resetDatabase($kernel);

        if ($isDAMADoctrineTestBundleEnabled) {
            // re-enable static connections
            StaticDriver::setKeepStaticConnections(true);
        }

        $kernel->shutdown();
    }

    /**
     * @internal
     * @before
     */
    public static function _resetSchema(): void
    {
        if (!\is_subclass_of(static::class, KernelTestCase::class)) {
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        $kernel = static::createKernel();
        $kernel->boot();

        DatabaseResetter::resetSchema($kernel);

        $kernel->shutdown();
    }
}
