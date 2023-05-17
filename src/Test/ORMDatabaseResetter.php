<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * @internal
 */
final class ORMDatabaseResetter extends AbstractSchemaResetter
{
    public const RESET_MODE_SCHEMA = 'schema';

    public const RESET_MODE_MIGRATE = 'migrate';

    /** @var list<string> */
    private array $connectionsToReset = [];

    /** @var list<string> */
    private array $objectManagersToReset = [];

    /**
     * @param list<string> $connectionsToReset
     * @param list<string> $objectManagersToReset
     */
    public function __construct(private Application $application, private ManagerRegistry $registry, array $connectionsToReset, array $objectManagersToReset, private string $resetMode)
    {
        self::validateObjectsToReset('connection', \array_keys($registry->getConnectionNames()), $connectionsToReset);
        $this->connectionsToReset = $connectionsToReset;

        self::validateObjectsToReset('object manager', \array_keys($registry->getManagerNames()), $objectManagersToReset);
        $this->objectManagersToReset = $objectManagersToReset;
    }

    public function resetDatabase(): void
    {
        $this->dropAndResetDatabase();
        $this->createSchema();
    }

    public function resetSchema(): void
    {
        if (DatabaseResetter::isDAMADoctrineTestBundleEnabled()) {
            // not required as the DAMADoctrineTestBundle wraps each test in a transaction
            return;
        }

        $this->dropSchema();
        $this->createSchema();
    }

    private function createSchema(): void
    {
        if ($this->isResetUsingMigrations()) {
            $this->runCommand($this->application, 'doctrine:migrations:migrate', ['-n' => true]);

            return;
        }

        foreach ($this->objectManagersToReset() as $manager) {
            $this->runCommand(
                $this->application,
                'doctrine:schema:create',
                [
                    '--em' => $manager,
                ]
            );
        }
    }

    private function dropSchema(): void
    {
        if ($this->isResetUsingMigrations()) {
            $this->dropAndResetDatabase();

            return;
        }

        foreach ($this->objectManagersToReset() as $manager) {
            $this->runCommand(
                $this->application,
                'doctrine:schema:drop',
                [
                    '--em' => $manager,
                    '--force' => true,
                ]
            );
        }
    }

    private function dropAndResetDatabase(): void
    {
        foreach ($this->connectionsToReset() as $connection) {
            $databasePlatform = $this->registry->getConnection($connection)->getDatabasePlatform();

            if ($databasePlatform instanceof PostgreSQLPlatform) {
                // let's drop all connections to the database to be able to drop it
                $this->runCommand(
                    $this->application,
                    'doctrine:query:sql',
                    [
                        '--connection' => $connection,
                        'sql' => 'SELECT pid, pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = current_database() AND pid <> pg_backend_pid()',
                    ],
                    canFail: true,
                );
            }

            $dropParams = ['--connection' => $connection, '--force' => true];

            if (!$databasePlatform instanceof SqlitePlatform) {
                // sqlite does not support "--if-exists" (ref: https://github.com/doctrine/dbal/pull/2402)
                $dropParams['--if-exists'] = true;
            }

            $this->runCommand($this->application, 'doctrine:database:drop', $dropParams);

            $this->runCommand(
                $this->application,
                'doctrine:database:create',
                [
                    '--connection' => $connection,
                ]
            );
        }
    }

    /** @return list<string> */
    private function connectionsToReset(): array
    {
        if (isset($_SERVER['FOUNDRY_RESET_CONNECTIONS'])) {
            trigger_deprecation('zenstruck\foundry', '1.23', 'Usage of environment variable "FOUNDRY_RESET_CONNECTIONS" is deprecated. Please use bundle configuration: "database_resetter.orm.connections: true".');

            return \explode(',', $_SERVER['FOUNDRY_RESET_CONNECTIONS']);
        }

        return $this->connectionsToReset ?: [$this->registry->getDefaultConnectionName()];
    }

    /** @return list<string> */
    private function objectManagersToReset(): array
    {
        if (isset($_SERVER['FOUNDRY_RESET_OBJECT_MANAGERS'])) {
            trigger_deprecation('zenstruck\foundry', '1.23', 'Usage of environment variable "FOUNDRY_RESET_OBJECT_MANAGERS" is deprecated. Please use bundle configuration: "database_resetter.orm.object_managers: true".');

            return \explode(',', $_SERVER['FOUNDRY_RESET_OBJECT_MANAGERS']);
        }

        return $this->objectManagersToReset ?: [$this->registry->getDefaultManagerName()];
    }

    private function isResetUsingMigrations(): bool
    {
        if (isset($_SERVER['FOUNDRY_RESET_MODE'])) {
            trigger_deprecation('zenstruck\foundry', '1.23', 'Usage of environment variable "FOUNDRY_RESET_MODE" is deprecated. Please use bundle configuration: "database_resetter.orm.reset_mode: true".');

            return self::RESET_MODE_MIGRATE === $_SERVER['FOUNDRY_RESET_MODE'];
        }

        return self::RESET_MODE_MIGRATE === $this->resetMode;
    }
}
