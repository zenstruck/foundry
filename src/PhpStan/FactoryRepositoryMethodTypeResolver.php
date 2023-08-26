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

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Zenstruck\Foundry\BaseFactory;
use Zenstruck\Foundry\Persistence\PersistenceManager;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @internal
 */
final class FactoryRepositoryMethodTypeResolver implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return BaseFactory::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'repository' === $methodReflection->getName();
    }

    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
    {
        $factoryMetadata = FactoryMetadata::getFactoryMetadata($methodCall, $methodReflection, $scope);

        if (!$factoryMetadata) {
            return null;
        }

        if (!PersistenceManager::classCanBePersisted($factoryMetadata->getTargetClass())) {
            return null;
        }

        return new GenericObjectType(RepositoryProxy::class, [new ObjectType($factoryMetadata->getTargetClass())]);
    }
}
