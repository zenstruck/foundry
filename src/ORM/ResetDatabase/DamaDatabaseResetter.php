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

namespace Zenstruck\Foundry\ORM\ResetDatabase;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class DamaDatabaseResetter implements OrmResetter
{
    public function __construct(
        private OrmResetter $decorated,
    ) {
    }

    public function resetBeforeFirstTest(KernelInterface $kernel): void
    {
        $isDAMADoctrineTestBundleEnabled = ResetDatabaseManager::isDAMADoctrineTestBundleEnabled();

        if (!$isDAMADoctrineTestBundleEnabled) {
            $this->decorated->resetBeforeFirstTest($kernel);

            return;
        }

        // disable static connections for this operation
        StaticDriver::setKeepStaticConnections(false);

        $this->decorated->resetBeforeFirstTest($kernel);

        if (PersistenceManager::isOrmOnly()) {
            // add global stories so they are available after transaction rollback
            Configuration::instance()->stories->loadGlobalStories();
        }

        // shutdown kernel before re-enabling static connections
        // this would prevent any error if any ResetInterface execute sql queries (example: symfony/doctrine-messenger)
        $kernel->shutdown();

        // re-enable static connections
        StaticDriver::setKeepStaticConnections(true);
    }

    public function resetBeforeEachTest(KernelInterface $kernel): void
    {
        if (ResetDatabaseManager::isDAMADoctrineTestBundleEnabled()) {
            // not required as the DAMADoctrineTestBundle wraps each test in a transaction
            return;
        }

        $this->decorated->resetBeforeEachTest($kernel);
    }
}
