<?php

namespace Zenstruck\Foundry\Test;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * @internal
 */
final class ORMDatabaseResetter extends AbstractSchemaResetter
{
    /** @var Application */
    private $application;
    /** @var ManagerRegistry */
    private $registry;
    /** @var list<string> */
    private $connectionsToReset;
    /** @var list<string> */
    private $objectManagersToReset;
    /** @var string */
    private $resetMode;

    /**
     * @param list<string> $connectionsToReset
     * @param list<string> $objectManagersToReset
     */
    public function __construct(Application $application, ManagerRegistry $registry, array $connectionsToReset, array $objectManagersToReset, string $resetMode)
    {
        $this->application = $application;
        $this->registry = $registry;

        self::validateObjectsToReset('connection', \array_keys($registry->getConnectionNames()), $connectionsToReset);
        $this->connectionsToReset = $connectionsToReset;

        self::validateObjectsToReset('object manager', \array_keys($registry->getManagerNames()), $objectManagersToReset);
        $this->objectManagersToReset = $objectManagersToReset;

        $this->resetMode = $resetMode;
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
            $dropParams = ['--connection' => $connection, '--force' => true];

            if ('sqlite' !== $this->registry->getConnection($connection)->getDatabasePlatform()->getName()) {
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

            return 'migrate' === $_SERVER['FOUNDRY_RESET_MODE'];
        }

        return 'migrate' === $this->resetMode;
    }
}
