<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Descriptors;

use PHPStan\Type\MixedType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class BinaryType implements DoctrineTypeDescriptor
{

	public function getType(): string
	{
		return \Doctrine\DBAL\Types\BinaryType::class;
	}

	public function getWritableToPropertyType(): Type
	{
		return new ResourceType();
	}

	public function getWritableToDatabaseType(): Type
	{
		return new MixedType();
	}

	public function getDatabaseInternalType(): Type
	{
		return new StringType();
	}

}
