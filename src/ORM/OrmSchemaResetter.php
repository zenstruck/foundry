<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\ORM;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Persistence\ResetDatabase\DatabaseResetterInterface;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseHandler;
use Zenstruck\Foundry\Persistence\ResetDatabase\SchemaResetterInterface;
use Zenstruck\Foundry\Persistence\SymfonyCommandRunner;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class OrmSchemaResetter implements SchemaResetterInterface
{
    use OrmDatabaseResetterTrait;
    use SymfonyCommandRunner;

    /**
     * @param list<string> $managers
     * @param list<string> $connections
     */
    public function __construct(
        private ManagerRegistry $registry,
        private array $managers,
        private array $connections,
    ) {
    }

    final public function resetSchema(KernelInterface $kernel): void
    {
        if (ResetDatabaseHandler::isDAMADoctrineTestBundleEnabled()) {
            // not required as the DAMADoctrineTestBundle wraps each test in a transaction
            return;
        }

        $application = self::application($kernel);

        $this->dropSchema($application);
        $this->createSchema($application);
    }

    private function dropSchema(Application $application): void
    {
//        if (self::RESET_MODE_MIGRATE === $this->config['reset']['mode']) {
//            $this->dropAndResetDatabase($application);
//
//            return;
//        }

        foreach ($this->managers() as $manager) {
            self::runCommand($application, 'doctrine:schema:drop', [
                '--em' => $manager,
                '--force' => true,
                '--full-database' => true,
            ]);
        }
    }

    private function registry(): ManagerRegistry
    {
        return $this->registry;
    }

    private function managers(): array
    {
        return $this->managers;
    }

    private function connections(): array
    {
        return $this->connections;
    }
}
