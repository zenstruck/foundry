<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\QueryBuilder;

use Doctrine\ORM\QueryBuilder;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Doctrine\DoctrineTypeUtils;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function strtolower;

class QueryBuilderMethodDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	private const MAX_COMBINATIONS = 16;

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
		$returnType = ParametersAcceptorSelector::selectSingle(
			$methodReflection->getVariants()
		)->getReturnType();
		if ($returnType instanceof MixedType) {
			return false;
		}
		return (new ObjectType(QueryBuilder::class))->isSuperTypeOf($returnType)->yes();
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): Type
	{
		$calledOnType = $scope->getType($methodCall->var);
		if (!$methodCall->name instanceof Identifier) {
			return $calledOnType;
		}
		$lowerMethodName = strtolower($methodCall->name->toString());
		if (in_array($lowerMethodName, [
			'setparameter',
			'setparameters',
		], true)) {
			return $calledOnType;
		}

		$queryBuilderTypes = DoctrineTypeUtils::getQueryBuilderTypes($calledOnType);
		if (count($queryBuilderTypes) === 0) {
			return $calledOnType;
		}

		if (count($queryBuilderTypes) > self::MAX_COMBINATIONS) {
			return $calledOnType;
		}

		$resultTypes = [];
		foreach ($queryBuilderTypes as $queryBuilderType) {
			$resultTypes[] = $queryBuilderType->append($methodCall);
		}

		return TypeCombinator::union(...$resultTypes);
	}

}
