<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Object;

/**
 * @internal
 */
final class PropertyLocator
{
    /**
     * Walks up the parents: \ReflectionClass::getProperty() does not see their private properties.
     *
     * @param object|class-string $objectOrClass
     */
    public static function find(object|string $objectOrClass, string $name): ?\ReflectionProperty
    {
        $class = new \ReflectionClass($objectOrClass);

        do {
            if ($class->hasProperty($name)) {
                return $class->getProperty($name);
            }
        } while ($class = $class->getParentClass());

        return null;
    }
}
