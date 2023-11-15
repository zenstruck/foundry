<?php declare(strict_types = 1);

namespace PHPStan\Rules\Gedmo;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Gedmo\Mapping\Annotation as Gedmo;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use function class_exists;
use function get_class;
use function in_array;

class PropertiesExtension implements ReadWritePropertiesExtension
{

	private const GEDMO_WRITE_CLASSLIST = [
		Gedmo\Blameable::class,
		Gedmo\IpTraceable::class,
		Gedmo\Locale::class,
		Gedmo\Language::class,
		Gedmo\Slug::class,
		Gedmo\SortablePosition::class,
		Gedmo\Timestampable::class,
		Gedmo\TreeLeft::class,
		Gedmo\TreeLevel::class,
		Gedmo\TreeParent::class,
		Gedmo\TreePath::class,
		Gedmo\TreePathHash::class,
		Gedmo\TreeRight::class,
		Gedmo\TreeRoot::class,
		Gedmo\UploadableFileMimeType::class,
		Gedmo\UploadableFileName::class,
		Gedmo\UploadableFilePath::class,
		Gedmo\UploadableFileSize::class,
	];

	private const GEDMO_READ_CLASSLIST = [
		Gedmo\Locale::class,
		Gedmo\Language::class,
	];

	/** @var AnnotationReader|null */
	private $annotationReader;

	/** @var ObjectMetadataResolver */
	private $objectMetadataResolver;

	public function __construct(ObjectMetadataResolver $objectMetadataResolver)
	{
		$this->annotationReader = class_exists(AnnotationReader::class) ? new AnnotationReader() : null;
		$this->objectMetadataResolver = $objectMetadataResolver;
	}

	public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
	{
		return $this->isGedmoAnnotationOrAttribute($property, $propertyName, self::GEDMO_READ_CLASSLIST);
	}

	public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
	{
		return $this->isGedmoAnnotationOrAttribute($property, $propertyName, self::GEDMO_WRITE_CLASSLIST);
	}

	public function isInitialized(PropertyReflection $property, string $propertyName): bool
	{
		return false;
	}

	/**
	 * @param array<class-string> $classList
	 */
	private function isGedmoAnnotationOrAttribute(PropertyReflection $property, string $propertyName, array $classList): bool
	{
		$classReflection = $property->getDeclaringClass();
		if ($this->objectMetadataResolver->isTransient($classReflection->getName())) {
			return false;
		}

		$propertyReflection = $classReflection->getNativeReflection()->getProperty($propertyName);

		$attributes = $propertyReflection->getAttributes();
		foreach ($attributes as $attribute) {
			if (in_array($attribute->getName(), $classList, true)) {
				return true;
			}
		}

		if ($this->annotationReader === null) {
			return false;
		}

		try {
			$annotations = $this->annotationReader->getPropertyAnnotations($propertyReflection);
		} catch (AnnotationException $e) {
			// Suppress the "The annotation X was never imported." exception in case the `objectManagerLoader` is not configured
			return false;
		}

		foreach ($annotations as $annotation) {
			if (in_array(get_class($annotation), $classList, true)) {
				return true;
			}
		}

		return false;
	}

}
