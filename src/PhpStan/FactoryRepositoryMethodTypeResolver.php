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

use Doctrine\ODM\MongoDB\Mapping\Annotations\Document as ODMDocumentAttribute;
use Doctrine\ORM\Mapping\Entity as ORMEntityAttribute;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Zenstruck\Foundry\BaseFactory;
use Zenstruck\Foundry\RepositoryProxy;

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
        $factoryMetadata = FactoryMetadata::getFactoryMetadata($methodCall, $scope);

        if (!$factoryMetadata) {
            return null;
        }

        if (!$this->hasPersistence($factoryMetadata->getTargetClass())) {
            return null;
        }

        return new GenericObjectType(RepositoryProxy::class, [new ObjectType($factoryMetadata->getTargetClass())]);
    }

    /**
     * @param class-string $templateTypeReferences
     */
    private function hasPersistence(string $templateTypeReferences): bool
    {
        // extract the repository class name from the attributes of the model class
        $attributes = (new \ReflectionClass($templateTypeReferences))->getAttributes(ORMEntityAttribute::class);
        if (!$attributes) {
            $attributes = (new \ReflectionClass($templateTypeReferences))->getAttributes(ODMDocumentAttribute::class);
        }

        return (bool) $attributes;
    }
}
