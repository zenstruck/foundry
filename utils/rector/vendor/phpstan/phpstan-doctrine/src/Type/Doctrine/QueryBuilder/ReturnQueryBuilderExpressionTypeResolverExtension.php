<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\QueryBuilder;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

class ReturnQueryBuilderExpressionTypeResolverExtension implements ExpressionTypeResolverExtension
{

	/** @var OtherMethodQueryBuilderParser */
	private $otherMethodQueryBuilderParser;

	public function __construct(
		OtherMethodQueryBuilderParser $otherMethodQueryBuilderParser
	)
	{
		$this->otherMethodQueryBuilderParser = $otherMethodQueryBuilderParser;
	}

	public function getType(Expr $expr, Scope $scope): ?Type
	{
		if (!$expr instanceof MethodCall && !$expr instanceof StaticCall) {
			return null;
		}

		if ($expr->isFirstClassCallable()) {
			return null;
		}

		$methodReflection = $this->getMethodReflection($expr, $scope);

		if ($methodReflection === null) {
			return null;
		}

		$returnType = ParametersAcceptorSelector::selectFromArgs($scope, $expr->getArgs(), $methodReflection->getVariants())->getReturnType();

		$returnsQueryBuilder = (new ObjectType(QueryBuilder::class))->isSuperTypeOf($returnType)->yes();

		if (!$returnsQueryBuilder) {
			return null;
		}

		$queryBuilderTypes = $this->otherMethodQueryBuilderParser->findQueryBuilderTypesInCalledMethod($scope, $methodReflection);
		if (count($queryBuilderTypes) === 0) {
			return null;
		}

		return TypeCombinator::union(...$queryBuilderTypes);
	}

	/**
	 * @param StaticCall|MethodCall $call
	 */
	private function getMethodReflection(CallLike $call, Scope $scope): ?MethodReflection
	{
		if (!$call->name instanceof Identifier) {
			return null;
		}

		if ($call instanceof MethodCall) {
			$callerType = $scope->getType($call->var);
		} else {
			if (!$call->class instanceof Name) {
				return null;
			}
			$callerType = $scope->resolveTypeByName($call->class);
		}

		$methodName = $call->name->name;

		foreach ($callerType->getObjectClassReflections() as $callerClassReflection) {
			if ($callerClassReflection->is(QueryBuilder::class)) {
				return null; // covered by QueryBuilderMethodDynamicReturnTypeExtension
			}
			if ($callerClassReflection->is(EntityRepository::class) && $methodName === 'createQueryBuilder') {
				return null; // covered by EntityRepositoryCreateQueryBuilderDynamicReturnTypeExtension
			}
			if ($callerClassReflection->is(EntityManagerInterface::class) && $methodName === 'createQueryBuilder') {
				return null; // no need to dive there
			}
		}

		return $scope->getMethodReflection($callerType, $methodName);
	}

}
