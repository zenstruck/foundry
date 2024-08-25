<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\ORM\ResetDatabase;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Persistence\ResetDatabase\DatabaseResetterInterface;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class DamaDatabaseResetter implements DatabaseResetterInterface
{
    public function __construct(
        private DatabaseResetterInterface $decorated,
    ) {
    }

    public function resetDatabase(KernelInterface $kernel): void
    {
        $isDAMADoctrineTestBundleEnabled = ResetDatabaseManager::isDAMADoctrineTestBundleEnabled();

        if (!$isDAMADoctrineTestBundleEnabled) {
            $this->decorated->resetDatabase($kernel);

            return;
        }

        // disable static connections for this operation
        StaticDriver::setKeepStaticConnections(false);

        $this->decorated->resetDatabase($kernel);

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
}
