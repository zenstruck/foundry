<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Descriptors;

use DateTime;
use DateTimeInterface;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class DateTimeTzType implements DoctrineTypeDescriptor
{

	public function getType(): string
	{
		return \Doctrine\DBAL\Types\DateTimeTzType::class;
	}

	public function getWritableToPropertyType(): Type
	{
		return new ObjectType(DateTime::class);
	}

	public function getWritableToDatabaseType(): Type
	{
		return new ObjectType(DateTimeInterface::class);
	}

	public function getDatabaseInternalType(): Type
	{
		return new StringType();
	}

}
