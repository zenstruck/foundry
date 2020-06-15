<?php

namespace Zenstruck\Foundry\Bridge;

use Doctrine\Persistence\ManagerRegistry;
use Zenstruck\Foundry\PersistenceManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait FoundryDataFixture
{
    /**
     * @required
     */
    public function register(ManagerRegistry $registry): void
    {
        PersistenceManager::register($registry);
    }
}
