<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Query;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

/** @api */
class QueryType extends GenericObjectType
{

	/** @var string */
	private $dql;

	public function __construct(string $dql, ?Type $indexType = null, ?Type $resultType = null)
	{
		parent::__construct('Doctrine\ORM\Query', [
			$indexType ?? new MixedType(),
			$resultType ?? new MixedType(),
		]);
		$this->dql = $dql;
	}

	public function equals(Type $type): bool
	{
		if ($type instanceof self) {
			return $this->getDql() === $type->getDql();
		}

		return parent::equals($type);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			return TrinaryLogic::createFromBoolean($this->equals($type));
		}

		return parent::isSuperTypeOf($type);
	}

	public function getDql(): string
	{
		return $this->dql;
	}

}
