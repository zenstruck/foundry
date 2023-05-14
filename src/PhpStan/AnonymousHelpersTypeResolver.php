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

        if (null === $argPosition) {
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
