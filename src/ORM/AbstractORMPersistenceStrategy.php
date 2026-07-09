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
    private int $withoutDoctrineEventsDepth = 0;

    /** @var array<string, list<object>> */
    private array $pendingGlobalListenerRestoration = [];

    /** @var array<class-string, array<string, list<array{class: class-string, method: string}>>> */
    private array $pendingEntityListenerRestoration = [];

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
        $isOutermost = 0 === $this->withoutDoctrineEventsDepth;
        ++$this->withoutDoctrineEventsDepth;

        // Entity listeners first: getClassMetadata() triggers loadClassMetadata which registers
        // #[AsEntityListener] listeners. Global listeners must still be active at that point.
        $this->removeEntityListeners($om, $entityClass, $disabledClasses);
        $this->removeGlobalListeners($om, $disabledClasses);

        try {
            return $callback();
        } finally {
            --$this->withoutDoctrineEventsDepth;

            // Both global and entity listeners are accumulated across nested calls and restored only once
            // when the outermost withoutDoctrineEvents callback completes.
            if ($isOutermost) {
                $this->restoreGlobalListeners($om);
                $this->restoreEntityListeners($om);
            }
        }
    }

    /**
     * @param list<class-string> $disabledClasses
     */
    private function removeGlobalListeners(EntityManagerInterface $om, array $disabledClasses): void
    {
        $eventManager = $om->getEventManager();

        foreach ($eventManager->getAllListeners() as $eventName => $listeners) {
            foreach ($listeners as $listener) {
                if ([] === $disabledClasses || \in_array($listener::class, $disabledClasses, true)) {
                    $eventManager->removeEventListener([$eventName], $listener);
                    $this->pendingGlobalListenerRestoration[$eventName][] = $listener;
                }
            }
        }
    }

    private function restoreGlobalListeners(EntityManagerInterface $om,): void
    {
        $eventManager = $om->getEventManager();

        foreach ($this->pendingGlobalListenerRestoration as $eventName => $listeners) {
            foreach ($listeners as $listener) {
                $eventManager->addEventListener([$eventName], $listener);
            }
        }
        $this->pendingGlobalListenerRestoration = [];
    }

    /**
     * @param class-string       $entityClass
     * @param list<class-string> $disabledClasses
     */
    private function removeEntityListeners(EntityManagerInterface $om, string $entityClass, array $disabledClasses): void
    {
        $metadata = $om->getClassMetadata($entityClass);
        $original = $metadata->entityListeners;

        if ([] === $original) {
            return;
        }

        $this->pendingEntityListenerRestoration[$entityClass] ??= $original;
        if ([] === $disabledClasses) {
            $metadata->entityListeners = [];

            return;
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
    }

    private function restoreEntityListeners(EntityManagerInterface $om): void
    {
        foreach ($this->pendingEntityListenerRestoration as $entityClass => $listeners) {
            $om->getClassMetadata($entityClass)->entityListeners = $listeners;
        }
        $this->pendingEntityListenerRestoration = [];
    }
}
