<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\ORM;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\Persistence\Mapping\MappingException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Persistence\PersistenceStrategy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @method EntityManagerInterface       objectManagerFor(string $class)
 * @method list<EntityManagerInterface> objectManagers()
 */
abstract class AbstractORMPersistenceStrategy extends PersistenceStrategy
{
    public const RESET_MODE_SCHEMA = 'schema';
    public const RESET_MODE_MIGRATE = 'migrate';

    final public function contains(object $object): bool
    {
        $em = $this->objectManagerFor($object::class);

        return $em->contains($object) && !$em->getUnitOfWork()->isScheduledForInsert($object);
    }

    final public function hasChanges(object $object): bool
    {
        $em = $this->objectManagerFor($object::class);

        if (!$em->contains($object)) {
            return false;
        }

        // cannot use UOW::recomputeSingleEntityChangeSet() here as it wrongly computes embedded objects as changed
        $em->getUnitOfWork()->computeChangeSet($em->getClassMetadata($object::class), $object);

        return (bool) $em->getUnitOfWork()->getEntityChangeSet($object);
    }

    final public function truncate(string $class): void
    {
        $this->objectManagerFor($class)->createQuery("DELETE {$class} e")->execute();
    }

    final public function embeddablePropertiesFor(object $object, string $owner): ?array
    {
        try {
            $metadata = $this->objectManagerFor($owner)->getClassMetadata($object::class);
        } catch (MappingException|ORMMappingException) {
            return null;
        }

        if (!$metadata->isEmbeddedClass) {
            return null;
        }

        $properties = [];

        foreach ($metadata->getFieldNames() as $field) {
            $properties[$field] = $metadata->getFieldValue($object, $field);
        }

        return $properties;
    }

    final public function isEmbeddable(object $object): bool
    {
        return $this->objectManagerFor($object::class)->getClassMetadata($object::class)->isEmbeddedClass;
    }

    final public function resetDatabase(KernelInterface $kernel): void
    {
        $application = self::application($kernel);

        $this->dropAndResetDatabase($application);
        $this->createSchema($application);
    }

    final public function resetSchema(KernelInterface $kernel): void
    {
        if (PersistenceManager::isDAMADoctrineTestBundleEnabled()) {
            // not required as the DAMADoctrineTestBundle wraps each test in a transaction
            return;
        }

        $application = self::application($kernel);

        $this->dropSchema($application);
        $this->createSchema($application);
    }

    final public function managedNamespaces(): array
    {
        $namespaces = [];

        foreach ($this->objectManagers() as $objectManager) {
            $namespaces[] = $objectManager->getConfiguration()->getEntityNamespaces();
        }

        return \array_values(\array_merge(...$namespaces));
    }

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

    private function dropSchema(Application $application): void
    {
        if (self::RESET_MODE_MIGRATE === $this->config['reset']['mode']) {
            $this->dropAndResetDatabase($application);

            return;
        }

        foreach ($this->managers() as $manager) {
            self::runCommand($application, 'doctrine:schema:drop', [
                '--em' => $manager,
                '--force' => true,
                '--full-database' => true,
            ]);
        }
    }

    /**
     * @return string[]
     */
    private function managers(): array
    {
        return $this->config['reset']['entity_managers'];
    }

    /**
     * @return string[]
     */
    private function connections(): array
    {
        return $this->config['reset']['connections'];
    }
}
