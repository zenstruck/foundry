<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\ObjectManager;
use PHPStan\Doctrine\Mapping\ClassMetadataFactory;
use PHPStan\ShouldNotHappenException;
use ReflectionException;
use function class_exists;
use function is_file;
use function is_readable;
use function sprintf;

final class ObjectMetadataResolver
{

	/** @var string|null */
	private $objectManagerLoader;

	/** @var ObjectManager|false|null */
	private $objectManager;

	/** @var ClassMetadataFactory|null */
	private $metadataFactory;

	/** @var string */
	private $tmpDir;

	public function __construct(
		?string $objectManagerLoader,
		string $tmpDir
	)
	{
		$this->objectManagerLoader = $objectManagerLoader;
		$this->tmpDir = $tmpDir;
	}

	public function hasObjectManagerLoader(): bool
	{
		return $this->objectManagerLoader !== null;
	}

	/** @api */
	public function getObjectManager(): ?ObjectManager
	{
		if ($this->objectManager === false) {
			return null;
		}

		if ($this->objectManager !== null) {
			return $this->objectManager;
		}

		if ($this->objectManagerLoader === null) {
			$this->objectManager = false;

			return null;
		}

		$this->objectManager = $this->loadObjectManager($this->objectManagerLoader);

		return $this->objectManager;
	}

	/**
	 * @param class-string $className
	 */
	public function isTransient(string $className): bool
	{
		if (!class_exists($className)) {
			return true;
		}

		$objectManager = $this->getObjectManager();

		try {
			if ($objectManager === null) {
				$metadataFactory = $this->getMetadataFactory();
				if ($metadataFactory === null) {
					return true;
				}

				return $metadataFactory->isTransient($className);
			}

			return $objectManager->getMetadataFactory()->isTransient($className);
		} catch (ReflectionException $e) {
			return true;
		}
	}

	private function getMetadataFactory(): ?ClassMetadataFactory
	{
		if ($this->metadataFactory !== null) {
			return $this->metadataFactory;
		}

		if (!class_exists(\Doctrine\ORM\Mapping\ClassMetadataFactory::class)) {
			return null;
		}

		return $this->metadataFactory = new ClassMetadataFactory($this->tmpDir);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return ClassMetadataInfo<T>|null
	 */
	public function getClassMetadata(string $className): ?ClassMetadataInfo
	{
		if ($this->isTransient($className)) {
			return null;
		}

		$objectManager = $this->getObjectManager();

		try {
			if ($objectManager === null) {
				$metadataFactory = $this->getMetadataFactory();
				if ($metadataFactory === null) {
					return null;
				}

				/** @throws \Doctrine\Persistence\Mapping\MappingException | MappingException | AnnotationException */
				$metadata = $metadataFactory->getMetadataFor($className);
			} else {
				/** @throws \Doctrine\Persistence\Mapping\MappingException | MappingException | AnnotationException */
				$metadata = $objectManager->getClassMetadata($className);
			}
		} catch (\Doctrine\Persistence\Mapping\MappingException | MappingException | AnnotationException $e) {
			return null;
		}

		if (!$metadata instanceof ClassMetadataInfo) {
			return null;
		}

		/** @var ClassMetadataInfo<T> $ormMetadata */
		$ormMetadata = $metadata;

		return $ormMetadata;
	}

	private function loadObjectManager(string $objectManagerLoader): ?ObjectManager
	{
		if (!is_file($objectManagerLoader)) {
			throw new ShouldNotHappenException(sprintf(
				'Object manager could not be loaded: file "%s" does not exist',
				$objectManagerLoader
			));
		}

		if (!is_readable($objectManagerLoader)) {
			throw new ShouldNotHappenException(sprintf(
				'Object manager could not be loaded: file "%s" is not readable',
				$objectManagerLoader
			));
		}

		return require $objectManagerLoader;
	}

}
