<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Descriptors;

use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class SimpleArrayType implements DoctrineTypeDescriptor
{

	public function getType(): string
	{
		return \Doctrine\DBAL\Types\SimpleArrayType::class;
	}

	public function getWritableToPropertyType(): Type
	{
		return AccessoryArrayListType::intersectWith(new ArrayType(new IntegerType(), new StringType()));
	}

	public function getWritableToDatabaseType(): Type
	{
		return new ArrayType(new MixedType(), new StringType());
	}

	public function getDatabaseInternalType(): Type
	{
		return new StringType();
	}

}
