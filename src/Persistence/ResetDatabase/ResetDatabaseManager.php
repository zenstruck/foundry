<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Persistence\ResetDatabase;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Exception\PersistenceNotAvailable;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Tests\Fixture\TestKernel;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ResetDatabaseManager
{
    private static bool $hasDatabaseBeenReset = false;

    /**
     * @param iterable<DatabaseResetterInterface> $databaseResetters
     * @param iterable<SchemaResetterInterface> $schemaResetters
     */
    public function __construct(
        private iterable $databaseResetters,
        private iterable $schemaResetters
    ) {
    }

    /**
     * @param callable():KernelInterface $createKernel
     * @param callable():void $shutdownKernel
     */
    public static function resetDatabase(callable $createKernel, callable $shutdownKernel): void
    {
        if (self::$hasDatabaseBeenReset) {
            return;
        }

        $kernel = $createKernel();
        $configuration = Configuration::instance();

        try {
            $databaseResetters = $configuration->persistence()->resetDatabaseManager()->databaseResetters;
        } catch (PersistenceNotAvailable $e) {
            if (!\class_exists(TestKernel::class)) {
                throw $e;
            }

            // allow this to fail if running foundry test suite
            return;
        }

        foreach ($databaseResetters as $databaseResetter) {
            $databaseResetter->resetDatabase($kernel);
        }

        $shutdownKernel();

        self::$hasDatabaseBeenReset = true;
    }

    /**
     * @param callable():KernelInterface $createKernel
     * @param callable():void $shutdownKernel
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
            $schemaResetters = $configuration->persistence()->resetDatabaseManager()->schemaResetters;
        } catch (PersistenceNotAvailable $e) {
            if (!\class_exists(TestKernel::class)) {
                throw $e;
            }

            // allow this to fail if running foundry test suite
            return;
        }

        foreach ($schemaResetters as $schemaResetter) {
            $schemaResetter->resetSchema($kernel);
        }

        $configuration->stories->loadGlobalStories();

        $shutdownKernel();
    }

    private static function canSkipSchemaReset(): bool
    {
        return PersistenceManager::isOrmOnly() && self::isDAMADoctrineTestBundleEnabled();
    }

    public static function isDAMADoctrineTestBundleEnabled(): bool
    {
        return \class_exists(StaticDriver::class) && StaticDriver::isKeepStaticConnections();
    }
}
