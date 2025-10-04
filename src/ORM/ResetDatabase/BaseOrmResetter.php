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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

use function Zenstruck\Foundry\runCommand;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 * @internal
 */
abstract class BaseOrmResetter implements OrmResetter
{
    private static bool $inFirstTest = true;

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

    final public function resetBeforeEachTest(KernelInterface $kernel): void
    {
        if (self::$inFirstTest) {
            self::$inFirstTest = false;

            return;
        }

        $this->doResetBeforeEachTest($kernel);
    }

    abstract protected function doResetBeforeEachTest(KernelInterface $kernel): void;

    final protected function dropAndResetDatabase(Application $application): void
    {
        foreach ($this->connections as $connectionName) {
            /** @var Connection $connection */
            $connection = $this->registry->getConnection($connectionName);
            $databasePlatform = $connection->getDatabasePlatform();

            if ($databasePlatform instanceof SQLitePlatform) {
                // we don't need to create the sqlite database - it's created when the schema is created
                // let's only drop the .db file

                $dbPath = $connection->getParams()['path'] ?? null; // @phpstan-ignore method.internal
                if ($dbPath && (new Filesystem())->exists($dbPath)) {
                    \file_put_contents($dbPath, '');
                }

                continue;
            }

            if ($databasePlatform instanceof PostgreSQLPlatform) {
                // let's drop all connections to the database to be able to drop it
                $sql = 'SELECT pid, pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = current_database() AND pid <> pg_backend_pid()';
                runCommand($application, "dbal:run-sql --connection={$connectionName} '{$sql}'", canFail: true);
            }

            runCommand($application, "doctrine:database:drop --connection={$connectionName} --force --if-exists");

            runCommand($application, "doctrine:database:create --connection={$connectionName}");
        }
    }
}
