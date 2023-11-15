<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine;

use Doctrine\DBAL\Types\Type;
use PHPStan\Type\Doctrine\Descriptors\DoctrineTypeDescriptor;

class DefaultDescriptorRegistry implements DescriptorRegistry
{

	/** @var array<class-string<Type>, DoctrineTypeDescriptor> */
	private $descriptors = [];

	/**
	 * @param DoctrineTypeDescriptor[] $descriptors
	 */
	public function __construct(array $descriptors)
	{
		foreach ($descriptors as $descriptor) {
			$this->descriptors[$descriptor->getType()] = $descriptor;
		}
	}

	public function get(string $type): DoctrineTypeDescriptor
	{
		$typesMap = Type::getTypesMap();
		if (!isset($typesMap[$type])) {
			throw new DescriptorNotRegisteredException();
		}

		/** @var class-string<Type> $typeClass */
		$typeClass = $typesMap[$type];
		if (!isset($this->descriptors[$typeClass])) {
			throw new DescriptorNotRegisteredException();
		}
		return $this->descriptors[$typeClass];
	}

}
