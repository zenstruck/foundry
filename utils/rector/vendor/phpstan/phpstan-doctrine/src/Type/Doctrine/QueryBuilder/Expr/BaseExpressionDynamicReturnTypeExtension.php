<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\QueryBuilder\Expr;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Doctrine\ORM\DynamicQueryBuilderArgumentException;
use PHPStan\Type\Doctrine\ArgumentsProcessor;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use function get_class;
use function in_array;
use function is_object;
use function method_exists;

class BaseExpressionDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	/** @var ArgumentsProcessor */
	private $argumentsProcessor;

	public function __construct(
		ArgumentsProcessor $argumentsProcessor
	)
	{
		$this->argumentsProcessor = $argumentsProcessor;
	}

	public function getClass(): string
	{
		return 'Doctrine\ORM\Query\Expr\Base';
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(), ['add', 'addMultiple'], true);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		$defaultReturnType = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();

		try {
			$args = $this->argumentsProcessor->processArgs($scope, $methodReflection->getName(), $methodCall->getArgs());
		} catch (DynamicQueryBuilderArgumentException $e) {
			return $defaultReturnType;
		}

		$calledOnType = $scope->getType($methodCall->var);
		if (!$calledOnType instanceof ExprType) {
			return $defaultReturnType;
		}

		$expr = $calledOnType->getExprObject();

		if (!method_exists($expr, $methodReflection->getName())) {
			return $defaultReturnType;
		}

		$exprValue = $expr->{$methodReflection->getName()}(...$args);
		if (is_object($exprValue)) {
			return new ExprType(get_class($exprValue), $exprValue);
		}

		return $scope->getTypeFromValue($exprValue);
	}

}
