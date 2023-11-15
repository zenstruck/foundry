<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Descriptors;

use JsonSerializable;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use stdClass;

class JsonType implements DoctrineTypeDescriptor
{

	private static function getJsonType(): UnionType
	{
		$mixedType = new MixedType();

		return new UnionType([
			new ArrayType($mixedType, $mixedType),
			new BooleanType(),
			new FloatType(),
			new IntegerType(),
			new NullType(),
			new ObjectType(JsonSerializable::class),
			new ObjectType(stdClass::class),
			new StringType(),
		]);
	}

	public function getType(): string
	{
		return \Doctrine\DBAL\Types\JsonType::class;
	}

	public function getWritableToPropertyType(): Type
	{
		return new NeverType();
	}

	public function getWritableToDatabaseType(): Type
	{
		return self::getJsonType();
	}

	public function getDatabaseInternalType(): Type
	{
		return new StringType();
	}

}
