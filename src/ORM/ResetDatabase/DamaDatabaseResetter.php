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
use Symfony\Component\HttpKernel\RebootableInterface;
use Zenstruck\Assert;
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
        private string $kernelBuildDir,
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

        // re-enable static connections
        StaticDriver::setKeepStaticConnections(true);

        if (!$kernel instanceof RebootableInterface) {
            throw new \InvalidArgumentException('Kernel should be rebootable to work with DAMADoctrineTestBundle.');
        }

        // let's reboot the kernel to ensure the static connections will be re-created
        $kernel->reboot($this->kernelBuildDir);
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
