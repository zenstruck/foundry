<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\ORM\ResetDatabase;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Zenstruck\Foundry\Persistence\SymfonyCommandRunner;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @internal
 */
abstract class BaseOrmResetter
{
    use SymfonyCommandRunner;

    /**
     * @param list<string> $managers
     * @param list<string> $connections
     */
    public function __construct(
        private readonly Registry $registry,
        protected readonly array $managers,
        protected readonly array $connections,
    ) {
    }

    final protected function dropAndResetDatabase(Application $application): void
    {
        foreach ($this->connections as $connectionName) {
            /** @var Connection $connection */
            $connection = $this->registry->getConnection($connectionName);
            $databasePlatform = $connection->getDatabasePlatform();

            if ($databasePlatform instanceof SQLitePlatform) {
                // we don't need to create the sqlite database - it's created when the schema is created
                continue;
            }

            if ($databasePlatform instanceof PostgreSQLPlatform) {
                // let's drop all connections to the database to be able to drop it
                self::runCommand(
                    $application,
                    'dbal:run-sql',
                    [
                        '--connection' => $connectionName,
                        'sql' => 'SELECT pid, pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = current_database() AND pid <> pg_backend_pid()',
                    ],
                    canFail: true,
                );
            }

            self::runCommand(
                $application,
                'doctrine:database:drop',
                ['--connection' => $connectionName, '--force' => true, '--if-exists' => true]
            );

            self::runCommand($application, 'doctrine:database:create', ['--connection' => $connectionName]);
        }
    }
}
