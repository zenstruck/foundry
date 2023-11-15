<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Doctrine;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;

class DoctrineSelectableClassReflectionExtension implements MethodsClassReflectionExtension
{

	/** @var ReflectionProvider */
	private $reflectionProvider;

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
	}

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $classReflection->getName() === 'Doctrine\Common\Collections\Collection'
			&& $methodName === 'matching';
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		$selectableReflection = $this->reflectionProvider->getClass('Doctrine\Common\Collections\Selectable');
		return $selectableReflection->getNativeMethod($methodName);
	}

}
