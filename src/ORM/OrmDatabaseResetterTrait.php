<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\ORM;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
trait OrmDatabaseResetterTrait
{
    private function dropAndResetDatabase(Application $application): void
    {
        foreach ($this->connections() as $connection) {
            $databasePlatform = $this->registry->getConnection($connection)->getDatabasePlatform(); // @phpstan-ignore-line

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
                        '--connection' => $connection,
                        'sql' => 'SELECT pid, pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = current_database() AND pid <> pg_backend_pid()',
                    ],
                    canFail: true,
                );
            }

            self::runCommand($application, 'doctrine:database:drop', [
                '--connection' => $connection,
                '--force' => true,
                '--if-exists' => true,
            ]);
            self::runCommand($application, 'doctrine:database:create', ['--connection' => $connection]);
        }
    }

    private function createSchema(Application $application): void
    {
        if (self::RESET_MODE_MIGRATE === $this->config['reset']['mode']) {
            self::runCommand($application, 'doctrine:migrations:migrate', [
                '--no-interaction' => true,
            ]);

            return;
        }

        foreach ($this->managers() as $manager) {
            self::runCommand($application, 'doctrine:schema:update', [
                '--em' => $manager,
                '--force' => true,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    abstract private function managers(): array;

    /**
     * @return list<string>
     */
    abstract private function connections(): array;
}
