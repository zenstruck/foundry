<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine;

use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Doctrine\ORM\DynamicQueryBuilderArgumentException;
use PHPStan\Type\Doctrine\QueryBuilder\Expr\ExprType;
use function count;
use function strpos;

/** @api */
class ArgumentsProcessor
{

	/**
	 * @param Arg[] $methodCallArgs
	 * @return list<mixed>
	 * @throws DynamicQueryBuilderArgumentException
	 */
	public function processArgs(
		Scope $scope,
		string $methodName,
		array $methodCallArgs
	): array
	{
		$args = [];
		foreach ($methodCallArgs as $arg) {
			$value = $scope->getType($arg->value);
			if (
				$value instanceof ExprType
				&& strpos($value->getClassName(), 'Doctrine\ORM\Query\Expr') === 0
			) {
				$args[] = $value->getExprObject();
				continue;
			}
			if (count($value->getConstantArrays()) === 1) {
				$array = [];
				foreach ($value->getConstantArrays()[0]->getKeyTypes() as $i => $keyType) {
					$valueType = $value->getConstantArrays()[0]->getValueTypes()[$i];
					if (count($valueType->getConstantScalarValues()) !== 1) {
						throw new DynamicQueryBuilderArgumentException();
					}
					$array[$keyType->getValue()] = $valueType->getConstantScalarValues()[0];
				}

				$args[] = $array;
				continue;
			}
			if (count($value->getConstantScalarValues()) !== 1) {
				throw new DynamicQueryBuilderArgumentException();
			}

			$args[] = $value->getConstantScalarValues()[0];
		}

		return $args;
	}

}
