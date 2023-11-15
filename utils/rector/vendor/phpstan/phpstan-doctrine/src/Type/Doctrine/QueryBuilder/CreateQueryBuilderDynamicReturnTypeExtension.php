<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\QueryBuilder;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

class CreateQueryBuilderDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	/** @var string|null */
	private $queryBuilderClass;

	/** @var bool */
	private $fasterVersion;

	public function __construct(
		?string $queryBuilderClass,
		bool $fasterVersion
	)
	{
		$this->queryBuilderClass = $queryBuilderClass;
		$this->fasterVersion = $fasterVersion;
	}

	public function getClass(): string
	{
		return 'Doctrine\ORM\EntityManagerInterface';
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
		$class = SimpleQueryBuilderType::class;
		if (!$this->fasterVersion) {
			$class = BranchingQueryBuilderType::class;
		}

		return new $class(
			$this->queryBuilderClass ?? 'Doctrine\ORM\QueryBuilder'
		);
	}

}
