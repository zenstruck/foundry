<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Descriptors\Ramsey;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Doctrine\Descriptors\DoctrineTypeDescriptor;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Doctrine\UuidBinaryType;
use Ramsey\Uuid\Doctrine\UuidType;
use Ramsey\Uuid\UuidInterface;
use function in_array;
use function sprintf;

class UuidTypeDescriptor implements DoctrineTypeDescriptor
{

	private const SUPPORTED_UUID_TYPES = [
		UuidType::class,
		UuidBinaryType::class,
		UuidBinaryOrderedTimeType::class,
	];

	/**
	 * @phpstan-var class-string<\Doctrine\DBAL\Types\Type>
	 * @var string
	 */
	private $uuidTypeName;

	public function __construct(
		string $uuidTypeName
	)
	{
		if (!in_array($uuidTypeName, self::SUPPORTED_UUID_TYPES, true)) {
			throw new ShouldNotHappenException(sprintf(
				'Unexpected UUID column type "%s" provided',
				$uuidTypeName
			));
		}

		$this->uuidTypeName = $uuidTypeName;
	}

	public function getType(): string
	{
		return $this->uuidTypeName;
	}

	public function getWritableToPropertyType(): Type
	{
		return new ObjectType(UuidInterface::class);
	}

	public function getWritableToDatabaseType(): Type
	{
		return TypeCombinator::union(
			new StringType(),
			new ObjectType(UuidInterface::class)
		);
	}

	public function getDatabaseInternalType(): Type
	{
		return new StringType();
	}

}
