<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\InMemory;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsInMemoryRepository
{
    public function __construct(
        public readonly string $class
    )
    {
        if (!class_exists($this->class)) {
            throw new \InvalidArgumentException("Wrong definition for \"AsInMemoryRepository\" attribute: class \"{$this->class}\" does not exist.");
        }
    }
}
