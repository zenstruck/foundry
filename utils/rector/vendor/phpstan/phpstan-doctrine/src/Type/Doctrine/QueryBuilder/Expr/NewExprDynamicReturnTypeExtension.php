<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\QueryBuilder\Expr;

use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Doctrine\ORM\DynamicQueryBuilderArgumentException;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Doctrine\ArgumentsProcessor;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function class_exists;

class NewExprDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	/** @var ArgumentsProcessor */
	private $argumentsProcessor;

	/** @var string */
	private $class;

	/** @var ReflectionProvider */
	private $reflectionProvider;

	public function __construct(
		ArgumentsProcessor $argumentsProcessor,
		string $class,
		ReflectionProvider $reflectionProvider
	)
	{
		$this->argumentsProcessor = $argumentsProcessor;
		$this->class = $class;
		$this->reflectionProvider = $reflectionProvider;
	}

	public function getClass(): string
	{
		return $this->class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type
	{
		if (!$methodCall->class instanceof Name) {
			throw new ShouldNotHappenException();
		}

		$className = $scope->resolveName($methodCall->class);
		if (!$this->reflectionProvider->hasClass($className)) {
			return new ObjectType($className);
		}

		if (!class_exists($className)) {
			return new ObjectType($className);
		}

		try {
			$exprObject = new $className(
				...$this->argumentsProcessor->processArgs(
					$scope,
					$methodReflection->getName(),
					$methodCall->getArgs()
				)
			);
		} catch (DynamicQueryBuilderArgumentException $e) {
			return new ObjectType($this->reflectionProvider->getClassName($className));
		}

		return new ExprType($className, $exprObject);
	}

}
