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
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use Doctrine\ORM\Mapping\OneToOneAssociationMapping;
use Doctrine\Persistence\Mapping\MappingException;
use Zenstruck\Foundry\Persistence\InitializeTrackedGhostsBeforeFlushListener;
use Zenstruck\Foundry\Persistence\PersistenceStrategy;
use Zenstruck\Foundry\Persistence\Relationship\ManyToOneRelationship;
use Zenstruck\Foundry\Persistence\Relationship\OneToManyRelationship;
use Zenstruck\Foundry\Persistence\Relationship\OneToOneRelationship;
use Zenstruck\Foundry\Persistence\Relationship\RelationshipMetadata;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @method EntityManagerInterface       objectManagerFor(string $class)
 * @method list<EntityManagerInterface> objectManagers()
 */
final class ORMPersistenceStrategy extends PersistenceStrategy
{
    public function contains(object $object): bool
    {
        $em = $this->objectManagerFor($object::class);

        return $em->contains($object) && !$em->getUnitOfWork()->isScheduledForInsert($object);
    }

    public function registerPreFlushGhostInitializer(string $class): void
    {
        $em = $this->objectManagerFor($class);

        $config = $em->getConfiguration();
        if (\method_exists($config, 'isNativeLazyObjectsEnabled') && $config->isNativeLazyObjectsEnabled()) {
            // ORM >= 3.4 with native lazy objects skips uninitialized objects when computing changesets
            return;
        }

        InitializeTrackedGhostsBeforeFlushListener::registerTo($em->getEventManager(), Events::preFlush);
    }

    public function hasChanges(object $object): bool
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

    public function isScheduledForInsert(object $object): bool
    {
        return $this->objectManagerFor($object::class)->getUnitOfWork()->isScheduledForInsert($object);
    }

    public function managedNamespaces(): array
    {
        $namespaces = [];

        foreach ($this->objectManagers() as $objectManager) {
            $namespaces[] = $objectManager->getConfiguration()->getEntityNamespaces();
        }

        return \array_values(\array_merge(...$namespaces));
    }

    public function getIdentifierValues(object $object): array
    {
        $identifiers = $this->classMetadata($object::class)->getIdentifierValues($object);

        // "Derived entities" could return an entity as part of the identifier array
        return \array_map(
            function(mixed $value) use ($object) {
                if (!\is_object($value) || !$this->objectManagerFor($object::class)->contains($value)) {
                    return $value;
                }

                $idValues = $this->classMetadata($value::class)->getIdentifierValues($value);

                // for now we don't support composite identifiers for derived entities
                return 1 === \count($idValues)
                    ? array_first($idValues)
                    : $idValues;
            },
            $identifiers
        );
    }

    public function disableDoctrineEvents(string $entityClass, array $disabledClasses): callable
    {
        $om = $this->objectManagerFor($entityClass);

        // Entity listeners first: getClassMetadata() triggers loadClassMetadata which registers
        // #[AsEntityListener] listeners. Global listeners must still be active at that point.
        $entityListenersBackup = $this->removeEntityListeners($om, $entityClass, $disabledClasses);
        $globalListenersBackup = $this->removeGlobalListeners($om, $disabledClasses);

        return function() use ($om, $entityClass, $entityListenersBackup, $globalListenersBackup): void {
            $this->restoreGlobalListeners($om, $globalListenersBackup);
            $this->restoreEntityListeners($om, $entityClass, $entityListenersBackup);
        };
    }

    /**
     * @param list<class-string> $disabledClasses
     *
     * @return array<string, list<object>>
     */
    private function removeGlobalListeners(EntityManagerInterface $om, array $disabledClasses): array
    {
        $eventManager = $om->getEventManager();
        $removed = [];

        foreach ($eventManager->getAllListeners() as $eventName => $listeners) {
            // Removing mapping infrastructure listeners (e.g. DoctrineBundle's AttachEntityListenersListener)
            // would permanently corrupt the metadata of any class loaded for the first time during the
            // disabling window: its #[AsEntityListener] listeners would be cached away forever.
            if ([] === $disabledClasses && \in_array($eventName, [Events::loadClassMetadata, Events::onClassMetadataNotFound], true)) {
                continue;
            }

            foreach ($listeners as $listener) {
                if ([] === $disabledClasses || \in_array($listener::class, $disabledClasses, true)) {
                    $eventManager->removeEventListener([$eventName], $listener);
                    $removed[$eventName][] = $listener;
                }
            }
        }

        return $removed;
    }

    /**
     * @param array<string, list<object>> $removedListeners
     */
    private function restoreGlobalListeners(EntityManagerInterface $om, array $removedListeners): void
    {
        $eventManager = $om->getEventManager();

        foreach ($removedListeners as $eventName => $listeners) {
            foreach ($listeners as $listener) {
                $eventManager->addEventListener([$eventName], $listener);
            }
        }
    }

    /**
     * @param class-string       $entityClass
     * @param list<class-string> $disabledClasses
     *
     * @return array<string, list<array{class: class-string, method: string}>>
     */
    private function removeEntityListeners(EntityManagerInterface $om, string $entityClass, array $disabledClasses): array
    {
        $metadata = $om->getClassMetadata($entityClass);
        $original = $metadata->entityListeners;

        if ([] === $original) {
            return [];
        }

        if ([] === $disabledClasses) {
            $metadata->entityListeners = [];

            return $original;
        }

        $metadata->entityListeners = \array_filter(
            \array_map(
                static fn(array $listeners) => \array_values(\array_filter(
                    $listeners,
                    static fn(array $listener) => !\in_array($listener['class'], $disabledClasses, true),
                )),
                $original,
            ),
            static fn(array $listeners) => [] !== $listeners,
        );

        return $original;
    }

    /**
     * @param class-string                                                    $entityClass
     * @param array<string, list<array{class: class-string, method: string}>> $original
     */
    private function restoreEntityListeners(EntityManagerInterface $om, string $entityClass, array $original): void
    {
        if ([] !== $original) {
            $om->getClassMetadata($entityClass)->entityListeners = $original;
        }
    }

    public function bidirectionalRelationshipMetadata(string $parent, string $child, string $field): ?RelationshipMetadata
    {
        $associationMapping = $this->getAssociationMapping($parent, $child, $field);

        if (null === $associationMapping) {
            return null;
        }

        if (!\is_a(
            $child,
            $associationMapping->targetEntity,
            allow_string: true
        )) { // is_a() handles inheritance as well
            throw new \LogicException("Cannot find correct association named \"{$field}\" between classes [parent: \"{$parent}\", child: \"{$child}\"]");
        }

        $inverseField = $associationMapping->isOwningSide() ? $associationMapping->inversedBy : $associationMapping->mappedBy;

        if (null === $inverseField) {
            return null;
        }

        return match (true) {
            $associationMapping instanceof OneToManyAssociationMapping => new OneToManyRelationship(
                inverseField: $inverseField,
                collectionIndexedBy: $associationMapping->isIndexed() ? $associationMapping->indexBy() : null
            ),
            $associationMapping instanceof OneToOneAssociationMapping => new OneToOneRelationship(
                inverseField: $inverseField,
                isOwning: $associationMapping->isOwningSide()
            ),
            $associationMapping instanceof ManyToOneAssociationMapping => new ManyToOneRelationship(
                inverseField: $inverseField,
            ),
            default => null,
        };
    }

    /**
     * @param class-string $entityClass
     */
    private function getAssociationMapping(string $entityClass, string $targetEntity, string $field): ?AssociationMapping
    {
        try {
            $associationMapping = $this->objectManagerFor($entityClass)->getClassMetadata($entityClass)->getAssociationMapping($field);
        } catch (MappingException|ORMMappingException) {
            return null;
        }

        if (!\is_a($targetEntity, $associationMapping->targetEntity, allow_string: true)) {
            return null;
        }

        return $associationMapping;
    }
}
