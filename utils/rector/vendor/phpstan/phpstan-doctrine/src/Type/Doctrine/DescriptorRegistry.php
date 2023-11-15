<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine;

use PHPStan\Type\Doctrine\Descriptors\DoctrineTypeDescriptor;

interface DescriptorRegistry
{

	public function get(string $type): DoctrineTypeDescriptor;

}
