<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\QueryBuilder;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use function array_unshift;
use function count;

class EntityRepositoryCreateQueryBuilderDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return 'Doctrine\ORM\EntityRepository';
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'createQueryBuilder';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): Type
	{
		$entityNameExpr = new MethodCall($methodCall->var, new Identifier('getEntityName'));

		$entityNameExprType = $scope->getType($entityNameExpr);
		if ($entityNameExprType->isClassStringType()->yes() && count($entityNameExprType->getClassStringObjectType()->getObjectClassNames()) === 1) {
			$entityNameExpr = new String_($entityNameExprType->getClassStringObjectType()->getObjectClassNames()[0]);
		}

		if (!isset($methodCall->getArgs()[0])) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		$fromArgs = $methodCall->getArgs();
		array_unshift($fromArgs, new Arg($entityNameExpr));

		$callStack = new MethodCall($methodCall->var, new Identifier('getEntityManager'));
		$callStack = new MethodCall($callStack, new Identifier('createQueryBuilder'));
		$callStack = new MethodCall($callStack, new Identifier('select'), [$methodCall->getArgs()[0]]);
		$callStack = new MethodCall($callStack, new Identifier('from'), $fromArgs);

		return $scope->getType($callStack);
	}

}
