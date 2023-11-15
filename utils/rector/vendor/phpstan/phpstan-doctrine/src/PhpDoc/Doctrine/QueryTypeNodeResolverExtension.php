<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolverAwareExtension;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use function count;

class QueryTypeNodeResolverExtension implements TypeNodeResolverExtension, TypeNodeResolverAwareExtension
{

	/** @var TypeNodeResolver */
	private $typeNodeResolver;

	public function setTypeNodeResolver(TypeNodeResolver $typeNodeResolver): void
	{
		$this->typeNodeResolver = $typeNodeResolver;
	}

	public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
	{
		if (!$typeNode instanceof GenericTypeNode) {
			return null;
		}

		$typeName = $nameScope->resolveStringName($typeNode->type->name);
		if ($typeName !== Query::class && $typeName !== AbstractQuery::class) {
			return null;
		}

		$count = count($typeNode->genericTypes);
		if ($count !== 1) {
			return null;
		}

		return new GenericObjectType(
			$typeName,
			[
				new NullType(),
				$this->typeNodeResolver->resolve($typeNode->genericTypes[0], $nameScope),
			]
		);
	}

}
