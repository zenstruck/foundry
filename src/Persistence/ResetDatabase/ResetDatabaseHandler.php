<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Persistence\ResetDatabase;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Exception\PersistenceNotAvailable;
use Zenstruck\Foundry\ORM\AbstractORMPersistenceStrategy;
use Zenstruck\Foundry\Tests\Fixture\TestKernel;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ResetDatabaseHandler
{
    private static bool $hasDatabaseBeenReset = false;
    private static bool $ormOnly = false;

    /**
     * @param callable():KernelInterface $createKernel
     * @param callable():void            $shutdownKernel
     */
    public static function resetDatabase(callable $createKernel, callable $shutdownKernel): void
    {
        if (self::$hasDatabaseBeenReset) {
            return;
        }

        if ($isDAMADoctrineTestBundleEnabled = self::isDAMADoctrineTestBundleEnabled()) {
            // disable static connections for this operation
            // :warning: the kernel should not be booted before calling this!
            StaticDriver::setKeepStaticConnections(false);
        }

        $kernel = $createKernel();
        $configuration = Configuration::instance();
        $strategyClasses = [];

        try {
            $strategies = $configuration->persistence()->databaseResetters;
        } catch (PersistenceNotAvailable $e) {
            if (!\class_exists(TestKernel::class)) {
                throw $e;
            }

            // allow this to fail if running foundry test suite
            return;
        }

        foreach ($strategies as $strategy) {
            $strategy->resetDatabase($kernel);
            $strategyClasses[] = $strategy::class;
        }

        if (1 === \count($strategyClasses) && \is_a($strategyClasses[0], AbstractORMPersistenceStrategy::class, allow_string: true)) {
            // enable skipping booting the kernel for resetSchema()
            self::$ormOnly = true;
        }

        if ($isDAMADoctrineTestBundleEnabled && self::$ormOnly) {
            // add global stories so they are available after transaction rollback
            $configuration->stories->loadGlobalStories();
        }

        if ($isDAMADoctrineTestBundleEnabled) {
            // re-enable static connections
            StaticDriver::setKeepStaticConnections(true);
        }

        $shutdownKernel();

        self::$hasDatabaseBeenReset = true;
    }

    /**
     * @param callable():KernelInterface $createKernel
     * @param callable():void            $shutdownKernel
     */
    public static function resetSchema(callable $createKernel, callable $shutdownKernel): void
    {
        if (self::canSkipSchemaReset()) {
            // can fully skip booting the kernel
            return;
        }

        $kernel = $createKernel();
        $configuration = Configuration::instance();

        try {
            $strategies = $configuration->persistence()->schemaResetters;
        } catch (PersistenceNotAvailable $e) {
            if (!\class_exists(TestKernel::class)) {
                throw $e;
            }

            // allow this to fail if running foundry test suite
            return;
        }

        foreach ($strategies as $strategy) {
            $strategy->resetSchema($kernel);
        }

        $configuration->stories->loadGlobalStories();

        $shutdownKernel();
    }

    private static function canSkipSchemaReset(): bool
    {
        return self::$ormOnly && self::isDAMADoctrineTestBundleEnabled();
    }

    public static function isDAMADoctrineTestBundleEnabled(): bool
    {
        return \class_exists(StaticDriver::class) && StaticDriver::isKeepStaticConnections();
    }
}
