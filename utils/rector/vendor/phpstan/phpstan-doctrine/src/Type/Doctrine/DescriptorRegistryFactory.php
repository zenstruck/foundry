<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine;

use PHPStan\DependencyInjection\Container;

class DescriptorRegistryFactory
{

	public const TYPE_DESCRIPTOR_TAG = 'phpstan.doctrine.typeDescriptor';

	/** @var Container */
	private $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function createRegistry(): DescriptorRegistry
	{
		return new DefaultDescriptorRegistry($this->container->getServicesByTag(self::TYPE_DESCRIPTOR_TAG));
	}

}
