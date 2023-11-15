<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Descriptors;

use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;

class SmallIntType implements DoctrineTypeDescriptor
{

	public function getType(): string
	{
		return \Doctrine\DBAL\Types\SmallIntType::class;
	}

	public function getWritableToPropertyType(): Type
	{
		return new IntegerType();
	}

	public function getWritableToDatabaseType(): Type
	{
		return new IntegerType();
	}

	public function getDatabaseInternalType(): Type
	{
		return new IntegerType();
	}

}
