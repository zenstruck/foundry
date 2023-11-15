<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ObjectManager;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;

class EntityManagerInterfaceThrowTypeExtension implements DynamicMethodThrowTypeExtension
{

	public const SUPPORTED_METHOD = [
		'flush' => [
			ORMException::class,
			UniqueConstraintViolationException::class,
		],
	];

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === ObjectManager::class
			&& isset(self::SUPPORTED_METHOD[$methodReflection->getName()]);
	}

	public function getThrowTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$type = $scope->getType($methodCall->var);

		if ((new ObjectType(EntityManagerInterface::class))->isSuperTypeOf($type)->yes()) {
			return TypeCombinator::union(
				...array_map(static function ($class): Type {
					return new ObjectType($class);
				}, self::SUPPORTED_METHOD[$methodReflection->getName()])
			);
		}

		return $methodReflection->getThrowType();
	}

}
