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
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;

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

    final public function managedNamespaces(): array
    {
        $namespaces = [];

        foreach ($this->objectManagers() as $objectManager) {
            $namespaces[] = $objectManager->getConfiguration()->getEntityNamespaces();
        }

        return \array_values(\array_merge(...$namespaces));
    }
}
