<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\PHPUnit;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class AttributeReader
{
    private function __construct()
    {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $attributeClass
     *
     * @return list<\ReflectionAttribute<T>>
     */
    public static function collectAttributesFromClassAndParents(string $attributeClass, \ReflectionClass $class): array // @phpstan-ignore missingType.generics
    {
        return [
            ...$class->getAttributes($attributeClass),
            ...(
                $class->getParentClass()
                    ? self::collectAttributesFromClassAndParents($attributeClass, $class->getParentClass())
                    : []
            ),
        ];
    }

    /**
     * @param class-string<object> $class
     * @param class-string<object> $attributeClass
     */
    public static function classOrParentsHasAttribute(string $class, string $attributeClass): bool
    {
        return [] !== self::collectAttributesFromClassAndParents($attributeClass, new \ReflectionClass($class));
    }
}
