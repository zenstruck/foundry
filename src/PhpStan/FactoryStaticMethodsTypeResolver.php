<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\PhpStan;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;
use Zenstruck\Foundry\BaseFactory;

final class FactoryStaticMethodsTypeResolver implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return BaseFactory::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array(
            $methodReflection->getName(),
            [
                'createOne',
                'find',
                'findOrCreate',
                'first',
                'last',
                'random',
                'randomOrCreate',
                'all',
                'createMany',
                'createSequence',
                'findBy',
                'randomRange',
                'randomSet',
            ],
            true
        );
    }

    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
    {
        $factoryMetadata = FactoryMetadata::getFactoryMetadata($methodCall, $scope);

        if (!$factoryMetadata) {
            return null;
        }

        return match ($methodReflection->getName()) {
            'createOne', 'find', 'findOrCreate', 'first', 'last', 'random', 'randomOrCreate' => $factoryMetadata->getSingleResultType(),
            'all', 'createMany', 'createSequence', 'findBy', 'randomRange', 'randomSet' => $factoryMetadata->getListResultType(),
            default => null
        };
    }
}
