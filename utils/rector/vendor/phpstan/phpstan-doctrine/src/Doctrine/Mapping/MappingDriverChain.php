<?php declare(strict_types = 1);

namespace PHPStan\Doctrine\Mapping;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

class MappingDriverChain implements MappingDriver
{

	/** @var MappingDriver[] */
	private $drivers;

	/**
	 * @param MappingDriver[] $drivers
	 */
	public function __construct(array $drivers)
	{
		$this->drivers = $drivers;
	}

	/**
	 * @param class-string $className
	 */
	public function loadMetadataForClass($className, ClassMetadata $metadata): void
	{
		foreach ($this->drivers as $driver) {
			try {
				$driver->loadMetadataForClass($className, $metadata);
				return;
			} catch (\Doctrine\Persistence\Mapping\MappingException | MappingException | AnnotationException $e) {
				// pass
			}
		}
	}

	public function getAllClassNames()
	{
		$all = [];
		foreach ($this->drivers as $driver) {
			foreach ($driver->getAllClassNames() as $className) {
				$all[] = $className;
			}
		}

		return $all;
	}

	/**
	 * @param class-string $className
	 */
	public function isTransient($className)
	{
		foreach ($this->drivers as $driver) {
			try {
				if ($driver->isTransient($className)) {
					continue;
				}

				return false;
			} catch (\Doctrine\Persistence\Mapping\MappingException | MappingException | AnnotationException $e) {
				// pass
			}
		}

		return true;
	}

}
