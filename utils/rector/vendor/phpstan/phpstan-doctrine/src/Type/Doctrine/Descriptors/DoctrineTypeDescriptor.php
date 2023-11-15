<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Descriptors;

use PHPStan\Type\Type;

/** @api */
interface DoctrineTypeDescriptor
{

	/**
	 * @return class-string<\Doctrine\DBAL\Types\Type>
	 */
	public function getType(): string;

	public function getWritableToPropertyType(): Type;

	public function getWritableToDatabaseType(): Type;

	public function getDatabaseInternalType(): Type;

}
