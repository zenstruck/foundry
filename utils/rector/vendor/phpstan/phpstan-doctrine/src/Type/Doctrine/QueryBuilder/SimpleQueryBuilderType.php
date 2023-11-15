<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\QueryBuilder;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use function count;

class SimpleQueryBuilderType extends QueryBuilderType
{

	public function equals(Type $type): bool
	{
		if ($type instanceof parent) {
			return count($this->getMethodCalls()) === count($type->getMethodCalls());
		}

		return parent::equals($type);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof parent) {
			$thisCount = count($this->getMethodCalls());
			$thatCount = count($type->getMethodCalls());

			return TrinaryLogic::createFromBoolean($thisCount === $thatCount);
		}

		return parent::isSuperTypeOf($type);
	}

}
