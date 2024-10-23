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
     * @param iterable<BeforeFirstTestResetter> $beforeFirstTestResetters
     * @param iterable<BeforeEachTestResetter>  $beforeEachTestResetter
     */
    public function __construct(
        private iterable $beforeFirstTestResetters,
        private iterable $beforeEachTestResetter,
    ) {
    }

    /**
     * @param callable():KernelInterface $createKernel
     * @param callable():void            $shutdownKernel
     */
    public static function resetBeforeFirstTest(callable $createKernel, callable $shutdownKernel): void
    {
        if (self::$hasDatabaseBeenReset) {
            return;
        }

        $kernel = $createKernel();
        $configuration = Configuration::instance();

        try {
            $databaseResetters = $configuration->persistence()->resetDatabaseManager()->beforeFirstTestResetters;
        } catch (PersistenceNotAvailable $e) {
            if (!\class_exists(TestKernel::class)) {
                throw $e;
            }

            // allow this to fail if running foundry test suite
            return;
        }

        foreach ($databaseResetters as $databaseResetter) {
            $databaseResetter->resetBeforeFirstTest($kernel);
        }

        $shutdownKernel();

        self::$hasDatabaseBeenReset = true;
    }

    /**
     * @param callable():KernelInterface $createKernel
     * @param callable():void            $shutdownKernel
     */
    public static function resetBeforeEachTest(callable $createKernel, callable $shutdownKernel): void
    {
        if (self::canSkipSchemaReset()) {
            // can fully skip booting the kernel
            return;
        }

        $kernel = $createKernel();
        $configuration = Configuration::instance();

        try {
            $beforeEachTestResetters = $configuration->persistence()->resetDatabaseManager()->beforeEachTestResetter;
        } catch (PersistenceNotAvailable $e) {
            if (!\class_exists(TestKernel::class)) {
                throw $e;
            }

            // allow this to fail if running foundry test suite
            return;
        }

        foreach ($beforeEachTestResetters as $beforeEachTestResetter) {
            $beforeEachTestResetter->resetBeforeEachTest($kernel);
        }

        $configuration->stories->loadGlobalStories();

        $shutdownKernel();
    }

    public static function isDAMADoctrineTestBundleEnabled(): bool
    {
        return \class_exists(StaticDriver::class) && StaticDriver::isKeepStaticConnections();
    }

    private static function canSkipSchemaReset(): bool
    {
        return PersistenceManager::isOrmOnly() && self::isDAMADoctrineTestBundleEnabled();
    }
}
