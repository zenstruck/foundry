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

use Zenstruck\Foundry\Object\Event\AfterInstantiate;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class ObjectRegistry
{
    /** @var array<string, array<string, object>> */
    private array $objects = [];

    public function store(string $factoryShortName, string $objectName, object $object): void
    {
        if ($this->has($factoryShortName, $objectName)) {
            throw ObjectAlreadyRegisteredException::forName($objectName);
        }

        $this->objects[$factoryShortName][$objectName] = $object;
    }

    public function get(string $factoryShortName, string $objectName): object
    {
        if (!$this->has($factoryShortName, $objectName)) {
            throw ObjectNotFoundException::forFactoryAndName($factoryShortName, $objectName);
        }

        return $this->objects[$factoryShortName][$objectName];
    }

    public function has(string $factoryShortName, string $objectName): bool
    {
        return isset($this->objects[$factoryShortName][$objectName]);
    }

    public function reset(): void
    {
        $this->objects = [];
    }
}
