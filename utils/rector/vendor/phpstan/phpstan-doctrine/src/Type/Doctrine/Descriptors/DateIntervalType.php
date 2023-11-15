<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Descriptors;

use DateInterval;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class DateIntervalType implements DoctrineTypeDescriptor
{

	public function getType(): string
	{
		return \Doctrine\DBAL\Types\DateIntervalType::class;
	}

	public function getWritableToPropertyType(): Type
	{
		return new ObjectType(DateInterval::class);
	}

	public function getWritableToDatabaseType(): Type
	{
		return new ObjectType(DateInterval::class);
	}

	public function getDatabaseInternalType(): Type
	{
		return new StringType();
	}

}
