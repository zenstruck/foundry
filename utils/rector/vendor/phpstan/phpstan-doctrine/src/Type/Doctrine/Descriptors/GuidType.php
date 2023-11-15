<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Descriptors;

use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class GuidType implements DoctrineTypeDescriptor
{

	public function getType(): string
	{
		return \Doctrine\DBAL\Types\GuidType::class;
	}

	public function getWritableToPropertyType(): Type
	{
		return new StringType();
	}

	public function getWritableToDatabaseType(): Type
	{
		return new StringType();
	}

	public function getDatabaseInternalType(): Type
	{
		return new StringType();
	}

}
