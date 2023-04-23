<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\PhpStan;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ORM\Mapping\Entity;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use Zenstruck\Foundry\BaseFactory;
use Zenstruck\Foundry\Object\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

final class AnonymousHelpersTypeResolver implements DynamicFunctionReturnTypeExtension
{
    public function getClass(): string
    {
        return BaseFactory::class;
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return \in_array(
            $functionReflection->getName(),
            [
                'Zenstruck\Foundry\create',
                'Zenstruck\Foundry\create_many',
            ],
            true
        );
    }

    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?\PHPStan\Type\Type
    {
        $targetClass = $this->extractTargetClass($functionCall);

        if (!$targetClass) {
            return null;
        }

        $reflectionClass = new \ReflectionClass($targetClass);
        if ($reflectionClass->getAttributes(Entity::class) || $reflectionClass->getAttributes(Document::class)) {
            $factoryClass = PersistentObjectFactory::class;
        } else {
            $factoryClass = ObjectFactory::class;
        }

        $factoryMetadata = new FactoryMetadata($factoryClass, $targetClass);

        return match ($functionCall->name->toString()) {
            'Zenstruck\Foundry\create' => $factoryMetadata->getSingleResultType(),
            'Zenstruck\Foundry\create_many' => $factoryMetadata->getFactoryCollectionResultType(),
            default => null
        };
    }

    private function extractTargetClass(FuncCall $functionCall): ?string
    {
        $argPosition = match ($functionCall->name->toString()) {
            'Zenstruck\Foundry\create' => 0,
            'Zenstruck\Foundry\create_many' => 1,
            default => null
        };

        if ($argPosition === null) {
            return null;
        }

        $targetClass = $functionCall->getArgs()[$argPosition]->value;

        if (!$targetClass instanceof ClassConstFetch) {
            return null;
        }

        if (!$targetClass->class instanceof FullyQualified) {
            return null;
        }

        return $targetClass->class->toString();
    }
}
