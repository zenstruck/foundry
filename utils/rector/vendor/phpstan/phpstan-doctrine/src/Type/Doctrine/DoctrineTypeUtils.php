<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine;

use PHPStan\Type\Doctrine\Query\QueryType;
use PHPStan\Type\Doctrine\QueryBuilder\QueryBuilderType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class DoctrineTypeUtils
{

	/**
	 * @return QueryBuilderType[]
	 */
	public static function getQueryBuilderTypes(Type $type): array
	{
		if ($type instanceof QueryBuilderType) {
			return [$type];
		}

		if ($type instanceof UnionType) {
			$types = [];
			foreach ($type->getTypes() as $innerType) {
				if (!$innerType instanceof QueryBuilderType) {
					return [];
				}

				$types[] = $innerType;
			}

			return $types;
		}

		return [];
	}

	/**
	 * @return QueryType[]
	 */
	public static function getQueryTypes(Type $type): array
	{
		if ($type instanceof QueryType) {
			return [$type];
		}

		if ($type instanceof UnionType) {
			$types = [];
			foreach ($type->getTypes() as $innerType) {
				if (!$innerType instanceof QueryType) {
					return [];
				}

				$types[] = $innerType;
			}

			return $types;
		}

		return [];
	}

}
