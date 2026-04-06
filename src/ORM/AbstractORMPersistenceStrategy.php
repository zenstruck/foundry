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

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectManager;
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

    final public function getIdentifierValues(object $object): array
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

    public function withoutDoctrineEvents(string $entityClass, array $disabledClasses, callable $callback): mixed
    {
        $om = $this->objectManagerFor($entityClass);

        // Entity listeners first: getClassMetadata() triggers loadClassMetadata which registers
        // #[AsEntityListener] listeners. Global listeners must still be active at that point.
        $entityListenersBackup = $this->removeEntityListeners($om, $entityClass, $disabledClasses);
        $globalListenersBackup = $this->removeGlobalListeners($om, $disabledClasses);

        try {
            return $callback();
        } finally {
            $this->restoreGlobalListeners($om, $globalListenersBackup);
            $this->restoreEntityListeners($om, $entityClass, $entityListenersBackup);
        }
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
}
