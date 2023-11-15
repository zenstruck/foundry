<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Descriptors;

use DateTimeImmutable;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class DateTimeImmutableType implements DoctrineTypeDescriptor
{

	public function getType(): string
	{
		return \Doctrine\DBAL\Types\DateTimeImmutableType::class;
	}

	public function getWritableToPropertyType(): Type
	{
		return new ObjectType(DateTimeImmutable::class);
	}

	public function getWritableToDatabaseType(): Type
	{
		return new ObjectType(DateTimeImmutable::class);
	}

	public function getDatabaseInternalType(): Type
	{
		return new StringType();
	}

}
