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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\Persistence\Mapping\MappingException;
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

        // we're cloning the UOW because computing change set has side effect
        $unitOfWork = clone $em->getUnitOfWork();

        // cannot use UOW::recomputeSingleEntityChangeSet() here as it wrongly computes embedded objects as changed
        $unitOfWork->computeChangeSet($em->getClassMetadata($object::class), $object);

        return (bool) $unitOfWork->getEntityChangeSet($object);
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

    final public function isScheduledForInsert(object $object): bool
    {
        return $this->objectManagerFor($object::class)->getUnitOfWork()->isScheduledForInsert($object);
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
