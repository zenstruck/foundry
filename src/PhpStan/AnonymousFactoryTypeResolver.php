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

final class AnonymousFactoryTypeResolver implements DynamicFunctionReturnTypeExtension
{
    public function getClass(): string
    {
        return BaseFactory::class;
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return 'Zenstruck\Foundry\anonymous' === $functionReflection->getName();
    }

    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?\PHPStan\Type\Type
    {
        $targetClass = $functionCall->getArgs()[0]->value;

        if (!$targetClass instanceof ClassConstFetch) {
            return null;
        }

        if (!$targetClass->class instanceof FullyQualified) {
            return null;
        }

        $reflectionClass = new \ReflectionClass($targetClass->class->toString());
        if ($reflectionClass->getAttributes(Entity::class) || $reflectionClass->getAttributes(Document::class)) {
            $factoryClass = PersistentObjectFactory::class;
        } else {
            $factoryClass = ObjectFactory::class;
        }

        return new GenericObjectType($factoryClass, [new ObjectType($targetClass->class->toString())]);
    }
}
