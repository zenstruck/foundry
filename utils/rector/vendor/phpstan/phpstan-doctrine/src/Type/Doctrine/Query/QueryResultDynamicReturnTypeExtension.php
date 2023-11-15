<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Query;

use Doctrine\ORM\AbstractQuery;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IterableType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VoidType;

final class QueryResultDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	private const METHOD_HYDRATION_MODE_ARG = [
		'getResult' => 0,
		'toIterable' => 1,
		'execute' => 1,
		'executeIgnoreQueryCache' => 1,
		'executeUsingQueryCache' => 1,
		'getOneOrNullResult' => 0,
		'getSingleResult' => 0,
	];

	public function getClass(): string
	{
		return AbstractQuery::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return isset(self::METHOD_HYDRATION_MODE_ARG[$methodReflection->getName()]);
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): Type
	{
		$methodName = $methodReflection->getName();

		if (!isset(self::METHOD_HYDRATION_MODE_ARG[$methodName])) {
			throw new ShouldNotHappenException();
		}

		$argIndex = self::METHOD_HYDRATION_MODE_ARG[$methodName];
		$args = $methodCall->getArgs();

		if (isset($args[$argIndex])) {
			$hydrationMode = $scope->getType($args[$argIndex]->value);
		} else {
			$parametersAcceptor = ParametersAcceptorSelector::selectSingle(
				$methodReflection->getVariants()
			);
			$parameter = $parametersAcceptor->getParameters()[$argIndex];
			$hydrationMode = $parameter->getDefaultValue() ?? new NullType();
		}

		$queryType = $scope->getType($methodCall->var);

		return $this->getMethodReturnTypeForHydrationMode(
			$methodReflection,
			$hydrationMode,
			$queryType->getTemplateType(AbstractQuery::class, 'TKey'),
			$queryType->getTemplateType(AbstractQuery::class, 'TResult')
		);
	}

	private function getMethodReturnTypeForHydrationMode(
		MethodReflection $methodReflection,
		Type $hydrationMode,
		Type $queryKeyType,
		Type $queryResultType
	): Type
	{
		$isVoidType = (new VoidType())->isSuperTypeOf($queryResultType);

		if ($isVoidType->yes()) {
			// A void query result type indicates an UPDATE or DELETE query.
			// In this case all methods return the number of affected rows.
			return new IntegerType();
		}

		if ($isVoidType->maybe()) {
			// We can't be sure what the query type is, so we return the
			// declared return type of the method.
			return $this->originalReturnType($methodReflection);
		}

		if (!$this->isObjectHydrationMode($hydrationMode)) {
			// We support only HYDRATE_OBJECT. For other hydration modes, we
			// return the declared return type of the method.
			return $this->originalReturnType($methodReflection);
		}

		switch ($methodReflection->getName()) {
			case 'getSingleResult':
				return $queryResultType;
			case 'getOneOrNullResult':
				return TypeCombinator::addNull($queryResultType);
			case 'toIterable':
				return new IterableType(
					$queryKeyType->isNull()->yes() ? new IntegerType() : $queryKeyType,
					$queryResultType
				);
			default:
				if ($queryKeyType->isNull()->yes()) {
					return AccessoryArrayListType::intersectWith(new ArrayType(
						new IntegerType(),
						$queryResultType
					));
				}
				return new ArrayType(
					$queryKeyType,
					$queryResultType
				);
		}
	}

	private function isObjectHydrationMode(Type $type): bool
	{
		if (!$type instanceof ConstantIntegerType) {
			return false;
		}

		return $type->getValue() === AbstractQuery::HYDRATE_OBJECT;
	}

	private function originalReturnType(MethodReflection $methodReflection): Type
	{
		$parametersAcceptor = ParametersAcceptorSelector::selectSingle(
			$methodReflection->getVariants()
		);

		return $parametersAcceptor->getReturnType();
	}

}
