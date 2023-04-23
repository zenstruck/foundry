<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\PhpStan;

use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use Zenstruck\Foundry\BaseFactory;
use Zenstruck\Foundry\FactoryCollection;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Proxy;

final class FactoryMetadata
{
    public function __construct(
        /** @var class-string<BaseFactory> */
        private string $factoryClass,
        /** @var class-string */
        private string $targetClass,
    )
    {
    }

    public static function getFactoryMetadata(CallLike $methodCall, Scope $scope): ?FactoryMetadata
    {
        $type = match (true) {
            $methodCall instanceof MethodCall => $scope->getType($methodCall->var),
            $methodCall instanceof StaticCall => new ObjectType($methodCall->class->toString()),
            default => null
        };

        if (!$type instanceof ObjectType) {
            return null;
        }

        if ($type instanceof GenericObjectType) {
            if (\count($type->getTypes()) !== 1 || !$type->getTypes()[0] instanceof ObjectType) {
                return null;
            }

            return new FactoryMetadata($type->getClassName(), $type->getTypes()[0]->getClassName());
        }

        try {
            $factoryReflection = new \ReflectionClass($type->getClassName());
        } catch (\ReflectionException) {
            return null;
        }

        if ($factoryReflection->isAbstract()) {
            return null;
        }

        return new FactoryMetadata($type->getClassName(), $type->getClassName()::class());
    }

    public function getSingleResultType(): ObjectType
    {
        return $this->hasPersistence()
            ? $this->getProxySingleResult()
            : new ObjectType($this->targetClass);
    }

    public function getListResultType(): ArrayType
    {
        return new ArrayType(
            new IntegerType(),
            $this->getSingleResultType()
        );
    }

    public function getFactoryCollectionResultType(): GenericObjectType
    {
        return new GenericObjectType(
            FactoryCollection::class,
            [
                $this->getSingleResultType()
            ]
        );
    }

    /**
     * @return class-string
     */
    public function getTargetClass(): string
    {
        return $this->targetClass;
    }

    private function getProxySingleResult(): GenericObjectType
    {
        return new GenericObjectType(Proxy::class, [new ObjectType($this->targetClass)]);
    }

    private function hasPersistence(): bool
    {
        return is_a($this->factoryClass, PersistentObjectFactory::class, true);
    }
}
