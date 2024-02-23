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
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\MappingException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Persistence\PersistenceStrategy;
use Zenstruck\Foundry\Persistence\RelationshipMetadata;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @method EntityManagerInterface objectManagerFor(string $class)
 * @method list<EntityManagerInterface> objectManagers()
 *
 * @phpstan-import-type AssociationMapping from \Doctrine\ORM\Mapping\ClassMetadata
 */
final class ORMPersistenceStrategy extends PersistenceStrategy
{
    public const RESET_MODE_SCHEMA = 'schema';
    public const RESET_MODE_MIGRATE = 'migrate';

    public function contains(object $object): bool
    {
        $em = $this->objectManagerFor($object::class);

        return $em->contains($object) && !$em->getUnitOfWork()->isScheduledForInsert($object);
    }

    public function hasChanges(object $object): bool
    {
        $em = $this->objectManagerFor($object::class);

        if (!$em->contains($object)) {
            return false;
        }

        // cannot use UOW::recomputeSingleEntityChangeSet() here as it wrongly computes embedded objects as changed
        $em->getUnitOfWork()->computeChangeSet($em->getClassMetadata($object::class), $object);

        return (bool) $em->getUnitOfWork()->getEntityChangeSet($object);
    }

    public function truncate(string $class): void
    {
        $this->objectManagerFor($class)->createQuery("DELETE {$class} e")->execute();
    }

    public function embeddablePropertiesFor(object $object, string $owner): ?array
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

    public function isEmbeddable(object $object): bool
    {
        return $this->objectManagerFor($object::class)->getClassMetadata($object::class)->isEmbeddedClass;
    }

    public function resetDatabase(KernelInterface $kernel): void
    {
        $application = self::application($kernel);

        foreach ($this->connections() as $connection) {
            $databasePlatform = $this->registry->getConnection($connection)->getDatabasePlatform(); // @phpstan-ignore-line

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

            $dropParams = ['--connection' => $connection, '--force' => true];

            if (!$databasePlatform instanceof SqlitePlatform) {
                // sqlite does not support "--if-exists" (ref: https://github.com/doctrine/dbal/pull/2402)
                $dropParams['--if-exists'] = true;
            }

            self::runCommand($application, 'doctrine:database:drop', $dropParams);
            self::runCommand($application, 'doctrine:database:create', ['--connection' => $connection]);
        }

        $this->createSchema($application);
    }

    public function resetSchema(KernelInterface $kernel): void
    {
        if (PersistenceManager::isDAMADoctrineTestBundleEnabled()) {
            // not required as the DAMADoctrineTestBundle wraps each test in a transaction
            return;
        }

        $application = self::application($kernel);

        $this->dropSchema($application);
        $this->createSchema($application);
    }

    public function relationshipMetadata(string $parent, string $child, string $field): ?RelationshipMetadata
    {
        $metadata = $this->classMetadata($parent);

        $association = $this->getAssociationMapping($parent, $field);

        if (null === $association) {
            $inversedAssociation = $this->getAssociationMapping($child, $field);

            if (null === $inversedAssociation || !$metadata instanceof ClassMetadataInfo) {
                return null;
            }

            if (!is_a($parent, $inversedAssociation['targetEntity'], allow_string: true)) { // is_a() handles inheritance as well
                throw new \LogicException("Cannot find correct association named \"$field\" between classes [parent: \"$parent\", child: \"$child\"]");
            }

            if ($inversedAssociation['type'] !== ClassMetadataInfo::ONE_TO_MANY || !isset($inversedAssociation['mappedBy'])) {
                return null;
            }

            $association = $metadata->getAssociationMapping($inversedAssociation['mappedBy']);
        }

        return new RelationshipMetadata(
            isCascadePersist: $association['isCascadePersist'],
            inverseField: $metadata->isSingleValuedAssociation($association['fieldName']) ? $association['fieldName'] : null,
        );
    }

    public function managedNamespaces(): array
    {
        $namespaces = [];

        foreach ($this->objectManagers() as $objectManager) {
            $namespaces[] = $objectManager->getConfiguration()->getEntityNamespaces();
        }

        return array_values(array_merge(...$namespaces));
    }

    /**
     * @param class-string $entityClass
     * @return array[]|null
     * @phpstan-return AssociationMapping|null
     */
    private function getAssociationMapping(string $entityClass, string $field): array|null
    {
        try {
            return $this->objectManagerFor($entityClass)->getClassMetadata($entityClass)->getAssociationMapping($field);
        } catch (MappingException|ORMMappingException) {
            return null;
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
