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
use Zenstruck\Foundry\Test\Behat\Exception\ObjectAlreadyRegistered;
use Zenstruck\Foundry\Test\Behat\Exception\ObjectNotFound;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ObjectRegistry
{
    /**
     * We need to use static properties in order that this is kept between kernel resets
     */

    /** @var array<class-string, array<string, object>> */
    private static array $objects = [];

    /** @var array<string, mixed> */
    private static array $lastId = [];

    public function __construct(
        private readonly FactoryShortNameResolver $factoryShortNameResolver,
        private readonly PersistenceManager $persistenceManager,
    ) {
    }

    /**
     * @param StateAddedToStory<object> $event
     */
    public function storeAfterStateAddedToStory(StateAddedToStory $event): void
    {
        $this->store($event->object, $event->name);
    }

    public function store(object $object, string $objectName): void
    {
        if ($this->has($object::class, $objectName)) {
            throw ObjectAlreadyRegistered::forClassAndName($object::class, $objectName);
        }

        self::$objects[$object::class][$objectName] = $object;
    }

    /**
     * @param class-string $objectClass
     */
    public function has(string $objectClass, string $objectName): bool
    {
        return isset(self::$objects[$objectClass][$objectName]);
    }

    /**
     * @param AfterPersist<object> $event
     */
    public function storeLastId(AfterPersist $event): void
    {
        self::$lastId = $this->persistenceManager->getIdentifierValues($event->object);
    }

    public function getByFactoryShortName(string $factoryShortName, string $objectName): object
    {
        $objectClass = $this->factoryShortNameResolver->targetObjectClassFor($factoryShortName);

        if (!$this->has($objectClass, $objectName)) {
            throw ObjectNotFound::forFactoryAndName($factoryShortName, $objectName);
        }

        return self::$objects[$objectClass][$objectName];
    }

    /**
     * @param class-string $objectClass
     *
     * @throws ObjectNotFound
     */
    public function getByObjectClass(string $objectClass, string $objectName): object
    {
        if (!$this->has($objectClass, $objectName)) {
            throw ObjectNotFound::forClassAndName($objectClass, $objectName);
        }

        return self::$objects[$objectClass][$objectName];
    }

    public function reset(): void
    {
        self::$objects = [];
        self::$lastId = [];
    }

    public function lastId(): int|string
    {
        if (!self::$lastId) {
            throw new \RuntimeException('No last id found.');
        }

        return $this->coerceIdToScalar(self::$lastId);
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
        $objects = self::$objects[$this->factoryShortNameResolver->targetObjectClassFor($factoryShortName)] ?? [];

        if (count($objects) === 0) {
            throw new \InvalidArgumentException("No object of type \"$factoryShortName\" found.");
        }

        return $this->coerceIdToScalar(
            $this->persistenceManager->getIdentifierValues(
                array_last($objects)
            )
        );
    }

    public function isStored(object $object): bool
    {
        return array_any(
            self::$objects[$object::class] ?? [],
            static fn(object $o) => $o === $object
        );
    }

    public function getNameFor(object $object): string
    {
        return array_find_key(
            self::$objects[$object::class] ?? [],
            static fn(object $o) => $o === $object
        ) ?? throw new \LogicException('Object is not stored in the registry.');
    }
}
