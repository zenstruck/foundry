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

namespace Zenstruck\Foundry\PhpStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\Proxy;

/**
 * @internal
 */
final class FactoryCollectionMethodsTypeResolver implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return FactoryCollection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'create' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $factoryCollectionType = $scope->getType($methodCall->var);

        if (
            !$factoryCollectionType instanceof GenericObjectType
            || !($targetType = $factoryCollectionType->getTypes()[0] ?? null) instanceof TypeWithClassName
        ) {
            return null;
        }

        $targetClass = $targetType->getClassName();

        if (Proxy::class === $targetClass) {
            $typeReturnedByCollection = $targetType;
        } else {
            $typeReturnedByCollection = PersistenceManager::classCanBePersisted($targetClass)
                ? new GenericObjectType(Proxy::class, [new ObjectType($targetClass)])
                : new ObjectType($targetClass);
        }

        return AccessoryArrayListType::intersectWith(
            new ArrayType(new IntegerType(), $typeReturnedByCollection)
        );
    }
}
