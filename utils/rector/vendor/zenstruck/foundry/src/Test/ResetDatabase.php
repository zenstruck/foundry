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

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;

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
        if (!\is_subclass_of(static::class, KernelTestCase::class)) {
            throw new \RuntimeException(\sprintf('The "%s" trait can only be used on TestCases that extend "%s".', __TRAIT__, KernelTestCase::class));
        }

        if ($isDAMADoctrineTestBundleEnabled = DatabaseResetter::isDAMADoctrineTestBundleEnabled()) {
            // disable static connections for this operation
            // :warning: the kernel should not be booted before calling this!
            StaticDriver::setKeepStaticConnections(false);
        }

        $kernel = static::createKernel();
        $kernel->boot();

        try {
            $kernel->getBundle('ZenstruckFoundryBundle');
        } catch (\InvalidArgumentException) {
            trigger_deprecation('zenstruck\foundry', '1.23', 'Usage of ResetDatabase trait without Foundry bundle is deprecated and will create an error in 2.0.');
        }

        if (self::shouldReset($kernel)) {
            DatabaseResetter::resetDatabase($kernel, $isDAMADoctrineTestBundleEnabled);
        }

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

    private static function shouldReset(KernelInterface $kernel): bool
    {
        if (isset($_SERVER['FOUNDRY_DISABLE_DATABASE_RESET'])) {
            trigger_deprecation('zenstruck\foundry', '1.23', 'Usage of environment variable "FOUNDRY_DISABLE_DATABASE_RESET" is deprecated. Please use bundle configuration: "database_resetter.disabled: true".');

            return false;
        }

        $configuration = self::getConfiguration($kernel->getContainer());

        if ($configuration && !$configuration->isDatabaseResetEnabled()) {
            return false;
        }

        return !DatabaseResetter::hasBeenReset();
    }

    private static function getConfiguration(ContainerInterface $container): ?Configuration
    {
        if ($container->has('.zenstruck_foundry.configuration')) {
            return $container->get('.zenstruck_foundry.configuration');
        }

        trigger_deprecation('zenstruck\foundry', '1.23', 'Usage of foundry without the bundle is deprecated and will not be possible anymore in 2.0.');

        return null;
    }
}
