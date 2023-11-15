<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\QueryBuilder;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use function array_keys;
use function count;

class BranchingQueryBuilderType extends QueryBuilderType
{

	public function equals(Type $type): bool
	{
		if ($type instanceof parent) {
			if (count($this->getMethodCalls()) !== count($type->getMethodCalls())) {
				return false;
			}

			foreach (array_keys($this->getMethodCalls()) as $id) {
				if (!isset($type->getMethodCalls()[$id])) {
					return false;
				}
			}

			foreach (array_keys($type->getMethodCalls()) as $id) {
				if (!isset($this->getMethodCalls()[$id])) {
					return false;
				}
			}

			return true;
		}

		return parent::equals($type);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof parent) {
			return TrinaryLogic::createFromBoolean($this->equals($type));
		}

		return parent::isSuperTypeOf($type);
	}

}
