<?php declare(strict_types = 1);

namespace PHPStan\Rules\Doctrine\ORM;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use Throwable;
use function array_key_exists;
use function in_array;

class PropertiesExtension implements ReadWritePropertiesExtension
{

	/** @var ObjectMetadataResolver */
	private $objectMetadataResolver;

	public function __construct(ObjectMetadataResolver $objectMetadataResolver)
	{
		$this->objectMetadataResolver = $objectMetadataResolver;
	}

	public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
	{
		$className = $property->getDeclaringClass()->getName();
		$metadata = $this->objectMetadataResolver->getClassMetadata($className);
		if ($metadata === null) {
			return false;
		}

		return $metadata->hasField($propertyName) || $metadata->hasAssociation($propertyName);
	}

	public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
	{
		$declaringClass = $property->getDeclaringClass();
		$className = $declaringClass->getName();
		$metadata = $this->objectMetadataResolver->getClassMetadata($className);
		if ($metadata === null) {
			return false;
		}

		if (!$metadata->hasField($propertyName) && !$metadata->hasAssociation($propertyName)) {
			return false;
		}

		if (isset($metadata->fieldMappings[$propertyName])) {
			$mapping = $metadata->fieldMappings[$propertyName];
			if (array_key_exists('generated', $mapping) && $mapping['generated'] !== ClassMetadataInfo::GENERATED_NEVER) {
				return true;
			}
		}

		if ($metadata->isReadOnly && !$declaringClass->hasConstructor()) {
			return true;
		}

		if ($metadata->versionField === $propertyName) {
			return true;
		}

		return $this->isGeneratedIdentifier($metadata, $propertyName);
	}

	public function isInitialized(PropertyReflection $property, string $propertyName): bool
	{
		$declaringClass = $property->getDeclaringClass();
		$className = $declaringClass->getName();
		$metadata = $this->objectMetadataResolver->getClassMetadata($className);
		if ($metadata === null) {
			return false;
		}

		if (!$metadata->hasField($propertyName) && !$metadata->hasAssociation($propertyName)) {
			return false;
		}

		if ($this->isGeneratedIdentifier($metadata, $propertyName)) {
			return true;
		}

		return $metadata->isReadOnly && !$declaringClass->hasConstructor();
	}

	/**
	 * @param ClassMetadataInfo<object> $metadata
	 */
	private function isGeneratedIdentifier(ClassMetadataInfo $metadata, string $propertyName): bool
	{
		if ($metadata->isIdentifierNatural()) {
			return false;
		}

		try {
			return in_array($propertyName, $metadata->getIdentifierFieldNames(), true);
		} catch (Throwable $e) {
			$mappingException = 'Doctrine\ORM\Mapping\MappingException';
			if (!$e instanceof $mappingException) {
				throw $e;
			}

			return false;
		}
	}

}
