<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\ORM;

use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Persistence\ResetDatabase\SchemaResetterInterface;
use Zenstruck\Foundry\Persistence\SymfonyCommandRunner;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class OrmMigrateSchemaResetter implements SchemaResetterInterface
{
    use OrmDatabaseResetterTrait;
    use SymfonyCommandRunner;

    public function resetSchema(KernelInterface $kernel): void
    {
        if (PersistenceManager::isDAMADoctrineTestBundleEnabled()) {
            // not required as the DAMADoctrineTestBundle wraps each test in a transaction
            return;
        }

        $application = self::application($kernel);

        $this->dropAndResetDatabase($application);
        $this->createSchema($application);
    }
}
