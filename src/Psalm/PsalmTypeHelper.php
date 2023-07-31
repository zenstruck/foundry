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

namespace Zenstruck\Foundry\Psalm;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ORM\Mapping\Entity;
use PhpParser\Node\Expr\ClassConstFetch;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Proxy;

final class PsalmTypeHelper
{
    public static function classType(string $targetClass): \Psalm\Type\Union
    {
        return new \Psalm\Type\Union([new \Psalm\Type\Atomic\TNamedObject($targetClass)]);
    }

    public static function genericType(string $baseClass, string $targetClass): \Psalm\Type\Union
    {
        return new \Psalm\Type\Union(
            [
                new \Psalm\Type\Atomic\TGenericObject(
                    $baseClass,
                    [self::classType($targetClass)]
                ),
            ]
        );
    }

    public static function genericTypeFromUnionType(string $baseClass, \Psalm\Type\Union $unionType): \Psalm\Type\Union
    {
        return new \Psalm\Type\Union([new \Psalm\Type\Atomic\TGenericObject($baseClass, [$unionType])]);
    }

    public static function factoryCollection(\Psalm\Type\Union $unionType): \Psalm\Type\Union
    {
        return self::genericTypeFromUnionType(
            FactoryCollection::class,
            self::genericTypeFromUnionType(Proxy::class, $unionType)
        );
    }

    public static function resolveFactoryTargetClass(\PhpParser\Node\Arg|null $arg): ?string
    {
        if (!$arg || !$arg->value instanceof ClassConstFetch) {
            return null;
        }

        return $arg->value->class->getAttributes()['resolvedName'];
    }

    public static function isFactoryTargetClassPersisted(string|null $factoryTargetClass): ?bool
    {
        if (!$factoryTargetClass) {
            return null;
        }

        $reflectionClass = new \ReflectionClass($factoryTargetClass);

        return $reflectionClass->getAttributes(Entity::class) || $reflectionClass->getAttributes(Document::class);
    }
}
