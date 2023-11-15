<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\QueryBuilder;

use AssertionError;
use Doctrine\Common\CommonException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Doctrine\ORM\DynamicQueryBuilderArgumentException;
use PHPStan\Type\Doctrine\ArgumentsProcessor;
use PHPStan\Type\Doctrine\DescriptorRegistry;
use PHPStan\Type\Doctrine\DoctrineTypeUtils;
use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use PHPStan\Type\Doctrine\Query\QueryResultTypeBuilder;
use PHPStan\Type\Doctrine\Query\QueryResultTypeWalker;
use PHPStan\Type\Doctrine\Query\QueryType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;
use function array_slice;
use function count;
use function in_array;
use function method_exists;
use function strtolower;

class QueryBuilderGetQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	/**
	 * Those are critical methods where we need to understand arguments passed to them, the rest is allowed to be more dynamic
	 * - this list reflects what is implemented in QueryResultTypeWalker
	 */
	private const METHODS_NOT_AFFECTING_RESULT_TYPE = [
		'where',
		'andwhere',
		'orwhere',
		'setparameter',
		'setparameters',
		'addcriteria',
		'addorderby',
		'orderby',
		'addgroupby',
		'groupby',
		'having',
		'andhaving',
		'orhaving',
	];

	/** @var ObjectMetadataResolver */
	private $objectMetadataResolver;

	/** @var ArgumentsProcessor */
	private $argumentsProcessor;

	/** @var string|null */
	private $queryBuilderClass;

	/** @var DescriptorRegistry */
	private $descriptorRegistry;

	public function __construct(
		ObjectMetadataResolver $objectMetadataResolver,
		ArgumentsProcessor $argumentsProcessor,
		?string $queryBuilderClass,
		DescriptorRegistry $descriptorRegistry
	)
	{
		$this->objectMetadataResolver = $objectMetadataResolver;
		$this->argumentsProcessor = $argumentsProcessor;
		$this->queryBuilderClass = $queryBuilderClass;
		$this->descriptorRegistry = $descriptorRegistry;
	}

	public function getClass(): string
	{
		return $this->queryBuilderClass ?? 'Doctrine\ORM\QueryBuilder';
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getQuery';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): Type
	{
		$calledOnType = $scope->getType($methodCall->var);
		$defaultReturnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$methodCall->getArgs(),
			$methodReflection->getVariants()
		)->getReturnType();
		$queryBuilderTypes = DoctrineTypeUtils::getQueryBuilderTypes($calledOnType);
		if (count($queryBuilderTypes) === 0) {
			return $defaultReturnType;
		}

		$objectManager = $this->objectMetadataResolver->getObjectManager();
		if ($objectManager === null) {
			return $defaultReturnType;
		}
		$entityManagerInterface = 'Doctrine\ORM\EntityManagerInterface';
		if (!$objectManager instanceof $entityManagerInterface) {
			return $defaultReturnType;
		}

		/** @var EntityManagerInterface $objectManager */
		$objectManager = $objectManager;

		$resultTypes = [];
		foreach ($queryBuilderTypes as $queryBuilderType) {
			$queryBuilder = $objectManager->createQueryBuilder();

			foreach ($queryBuilderType->getMethodCalls() as $calledMethodCall) {
				if (!$calledMethodCall->name instanceof Identifier) {
					continue;
				}

				$methodName = $calledMethodCall->name->toString();
				$lowerMethodName = strtolower($methodName);
				if (in_array($lowerMethodName, [
					'setparameter',
					'setparameters',
				], true)) {
					continue;
				}

				if ($lowerMethodName === 'setfirstresult') {
					$queryBuilder->setFirstResult(0);
					continue;
				}

				if ($lowerMethodName === 'setmaxresults') {
					$queryBuilder->setMaxResults(10);
					continue;
				}

				if ($lowerMethodName === 'set') {
					try {
						$args = $this->argumentsProcessor->processArgs($scope, $methodName, array_slice($calledMethodCall->getArgs(), 0, 1));
					} catch (DynamicQueryBuilderArgumentException $e) {
						return $defaultReturnType;
					}
					if (count($args) === 1) {
						$queryBuilder->set($args[0], $args[0]);
						continue;
					}
				}

				if (!method_exists($queryBuilder, $methodName)) {
					continue;
				}

				try {
					$args = $this->argumentsProcessor->processArgs($scope, $methodName, $calledMethodCall->getArgs());
				} catch (DynamicQueryBuilderArgumentException $e) {
					if (in_array($lowerMethodName, self::METHODS_NOT_AFFECTING_RESULT_TYPE, true)) {
						continue;
					}
					return $defaultReturnType;
				}

				try {
					$queryBuilder->{$methodName}(...$args);
				} catch (Throwable $e) {
					return $defaultReturnType;
				}
			}

			$resultTypes[] = $this->getQueryType($queryBuilder->getDQL());
		}

		return TypeCombinator::union(...$resultTypes);
	}

	private function getQueryType(string $dql): Type
	{
		$em = $this->objectMetadataResolver->getObjectManager();
		if (!$em instanceof EntityManagerInterface) {
			return new QueryType($dql, null);
		}

		$typeBuilder = new QueryResultTypeBuilder();

		try {
			$query = $em->createQuery($dql);
			QueryResultTypeWalker::walk($query, $typeBuilder, $this->descriptorRegistry);
		} catch (ORMException | DBALException | CommonException $e) {
			return new QueryType($dql, null);
		} catch (AssertionError $e) {
			return new QueryType($dql, null);
		}

		return new QueryType($dql, $typeBuilder->getIndexType(), $typeBuilder->getResultType());
	}

}
