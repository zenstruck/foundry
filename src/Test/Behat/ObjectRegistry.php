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

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ObjectRegistry
{
    /** @var array<class-string, array<string, object>> */
    private array $objects = [];

    public function store(object $object, string $objectName, string $factoryShortName): void
    {
        if ($this->has($object::class, $objectName)) {
            throw ObjectAlreadyRegisteredException::forFactoryAndName($factoryShortName, $objectName);
        }

        $this->objects[$object::class][$objectName] = $object;
    }

    /**
     * @param class-string $objectClass
     */
    public function get(string $factoryShortName, string $objectClass, string $objectName): object
    {
        if (!$this->has($objectClass, $objectName)) {
            throw ObjectNotFoundException::forFactoryAndName($factoryShortName, $objectName);
        }

        return $this->objects[$objectClass][$objectName];
    }

    /**
     * @param class-string $objectClass
     */
    public function has(string $objectClass, string $objectName): bool
    {
        return isset($this->objects[$objectClass][$objectName]);
    }

    public function reset(): void
    {
        $this->objects = [];
    }
}
