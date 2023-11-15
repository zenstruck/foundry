<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Descriptors;

use PHPStan\Type\MixedType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\Type;

class BlobType implements DoctrineTypeDescriptor
{

	public function getType(): string
	{
		return \Doctrine\DBAL\Types\BlobType::class;
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
		return new MixedType();
	}

}
