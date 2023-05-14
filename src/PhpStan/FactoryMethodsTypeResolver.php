<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\PhpStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use Zenstruck\Foundry\BaseFactory;

final class FactoryMethodsTypeResolver implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return BaseFactory::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array(
            $methodReflection->getName(),
            [
                'create',
                'many',
                'sequence',
            ],
            true
        );
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $factoryMetadata = FactoryMetadata::getFactoryMetadata($methodCall, $scope);

        if (!$factoryMetadata) {
            return null;
        }

        return match ($methodReflection->getName()) {
            'create' => $factoryMetadata->getSingleResultType(),
            'many', 'sequence' => $factoryMetadata->getFactoryCollectionResultType(),
            default => null
        };
    }
}
