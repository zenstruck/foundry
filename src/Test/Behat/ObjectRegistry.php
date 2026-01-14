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

namespace Zenstruck\Foundry\Test\Behat;

use Zenstruck\Foundry\Persistence\Event\AfterPersist;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Story\Event\StateAddedToStory;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ObjectRegistry
{
    /** @var array<class-string, array<string, object>> */
    private array $objects = [];

    /** @var array<string, mixed> */
    private array $lastId = [];

    public function __construct(
        private readonly FactoryShortNameResolver $factoryShortNameResolver,
        private readonly PersistenceManager $persistenceManager,
    ) {
    }

    public function store(object $object, string $objectName): void
    {
        if ($this->has($object::class, $objectName)) {
            throw ObjectAlreadyRegisteredException::forClassAndName($object::class, $objectName);
        }

        $this->objects[$object::class][$objectName] = $object;
    }

    /**
     * @param StateAddedToStory<object> $event
     */
    public function storeAfterStateAddedToStory(StateAddedToStory $event): void
    {
        $this->store($event->object, $event->name);
    }

    /**
     * @param class-string $objectClass
     */
    public function has(string $objectClass, string $objectName): bool
    {
        return isset($this->objects[$objectClass][$objectName]);
    }

    /**
     * @param AfterPersist<object> $event
     */
    public function storeLastId(AfterPersist $event): void
    {
        $this->lastId = $this->persistenceManager->getIdentifierValues($event->object);
    }

    public function get(string $factoryShortName, string $objectName): object
    {
        $objectClass = $this->factoryShortNameResolver->targetObjectClassFor($factoryShortName);

        if (!$this->has($objectClass, $objectName)) {
            throw ObjectNotFoundException::forFactoryAndName($factoryShortName, $objectName);
        }

        return $this->objects[$objectClass][$objectName];
    }

    public function reset(): void
    {
        $this->objects = [];
        $this->lastId = [];
    }

    public function lastId(): int|string
    {
        if (!$this->lastId) {
            throw new \RuntimeException('No last id found.');
        }

        return $this->coerceIdToScalar($this->lastId);
    }

    /**
     * @param array<string, mixed> $ids
     */
    private function coerceIdToScalar(array $ids): int|string
    {
        if (count($ids) !== 1) {
            throw new \InvalidArgumentException('Cannot get last id: generic entity must have exactly one identifier.');
        }

        $id = array_first($ids);
        if (!is_int($id) && !is_string($id)) {
            throw new \InvalidArgumentException(sprintf('Wrong type for the id: expected int or string, got "%s".', get_debug_type($id)));
        }

        return $id;
    }

    public function lastIdFor(string $factoryShortName): int|string
    {
        $objects = $this->objects[$this->factoryShortNameResolver->targetObjectClassFor($factoryShortName)] ?? [];

        if (count($objects) === 0) {
            throw new \InvalidArgumentException("No object of type \"$factoryShortName\" found.");
        }

        return $this->coerceIdToScalar(
            $this->persistenceManager->getIdentifierValues(
                array_last($objects)
            )
        );
    }
}
