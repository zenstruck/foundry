<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\QueryBuilder;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class QueryBuilderGetDqlDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	/** @var string|null */
	private $queryBuilderClass;

	public function __construct(
		?string $queryBuilderClass
	)
	{
		$this->queryBuilderClass = $queryBuilderClass;
	}

	public function getClass(): string
	{
		return $this->queryBuilderClass ?? 'Doctrine\ORM\QueryBuilder';
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getDQL';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): Type
	{
		$type = $scope->getType(new MethodCall(
			new MethodCall($methodCall->var, new Identifier('getQuery')),
			new Identifier('getDQL')
		));

		return TypeCombinator::removeNull($type);
	}

}
