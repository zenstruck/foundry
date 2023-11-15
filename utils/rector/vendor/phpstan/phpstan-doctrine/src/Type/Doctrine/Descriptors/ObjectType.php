<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Descriptors;

use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class ObjectType implements DoctrineTypeDescriptor
{

	public function getType(): string
	{
		return \Doctrine\DBAL\Types\ObjectType::class;
	}

	public function getWritableToPropertyType(): Type
	{
		return new ObjectWithoutClassType();
	}

	public function getWritableToDatabaseType(): Type
	{
		return new ObjectWithoutClassType();
	}

	public function getDatabaseInternalType(): Type
	{
		return new StringType();
	}

}
