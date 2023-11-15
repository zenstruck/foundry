<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine;

use AssertionError;
use Doctrine\Common\CommonException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception as NewDBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Doctrine\Query\QueryResultTypeBuilder;
use PHPStan\Type\Doctrine\Query\QueryResultTypeWalker;
use PHPStan\Type\Doctrine\Query\QueryType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

/**
 * Infers TResult in Query<TResult> on EntityManagerInterface::createQuery()
 */
final class CreateQueryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	/** @var ObjectMetadataResolver */
	private $objectMetadataResolver;

	/** @var DescriptorRegistry */
	private $descriptorRegistry;

	public function __construct(ObjectMetadataResolver $objectMetadataResolver, DescriptorRegistry $descriptorRegistry)
	{
		$this->objectMetadataResolver = $objectMetadataResolver;
		$this->descriptorRegistry = $descriptorRegistry;
	}

	public function getClass(): string
	{
		return EntityManagerInterface::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'createQuery';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): Type
	{
		$queryStringArgIndex = 0;
		$args = $methodCall->getArgs();

		if (!isset($args[$queryStringArgIndex])) {
			return new GenericObjectType(
				Query::class,
				[new MixedType(), new MixedType()]
			);
		}

		$argType = $scope->getType($args[$queryStringArgIndex]->value);

		return TypeTraverser::map($argType, function (Type $type, callable $traverse): Type {
			if ($type instanceof UnionType || $type instanceof IntersectionType) {
				return $traverse($type);
			}
			if ($type instanceof ConstantStringType) {
				$queryString = $type->getValue();

				$em = $this->objectMetadataResolver->getObjectManager();
				if (!$em instanceof EntityManagerInterface) {
					return new QueryType($queryString, null, null);
				}

				$typeBuilder = new QueryResultTypeBuilder();

				try {
					$query = $em->createQuery($queryString);
					QueryResultTypeWalker::walk($query, $typeBuilder, $this->descriptorRegistry);
				} catch (ORMException | DBALException | NewDBALException | CommonException $e) {
					return new QueryType($queryString, null, null);
				} catch (AssertionError $e) {
					return new QueryType($queryString, null, null);
				}

				return new QueryType($queryString, $typeBuilder->getIndexType(), $typeBuilder->getResultType());
			}
			return new GenericObjectType(
				Query::class,
				[new MixedType(), new MixedType()]
			);
		});
	}

}
