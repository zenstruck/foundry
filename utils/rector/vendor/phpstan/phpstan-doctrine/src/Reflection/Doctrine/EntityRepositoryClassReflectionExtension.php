<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Doctrine;

use Doctrine\Persistence\ObjectRepository;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\TypeCombinator;
use function lcfirst;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function ucwords;

class EntityRepositoryClassReflectionExtension implements MethodsClassReflectionExtension
{

	/** @var ObjectMetadataResolver */
	private $objectMetadataResolver;

	public function __construct(ObjectMetadataResolver $objectMetadataResolver)
	{
		$this->objectMetadataResolver = $objectMetadataResolver;
	}

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		if (
			strpos($methodName, 'findBy') === 0
			&& strlen($methodName) > strlen('findBy')
		) {
			$methodFieldName = substr($methodName, strlen('findBy'));
		} elseif (
			strpos($methodName, 'findOneBy') === 0
			&& strlen($methodName) > strlen('findOneBy')
		) {
			$methodFieldName = substr($methodName, strlen('findOneBy'));
		} elseif (
			strpos($methodName, 'countBy') === 0
			&& strlen($methodName) > strlen('countBy')
		) {
			$methodFieldName = substr($methodName, strlen('countBy'));
		} else {
			return false;
		}

		$repositoryAncesor = $classReflection->getAncestorWithClassName(ObjectRepository::class);
		if ($repositoryAncesor === null) {
			return false;
		}

		$templateTypeMap = $repositoryAncesor->getActiveTemplateTypeMap();
		$entityClassType = $templateTypeMap->getType('TEntityClass');
		if ($entityClassType === null) {
			return false;
		}

		$entityClassNames = $entityClassType->getObjectClassNames();
		$fieldName = $this->classify($methodFieldName);

		/** @var class-string $entityClassName */
		foreach ($entityClassNames as $entityClassName) {
			$classMetadata = $this->objectMetadataResolver->getClassMetadata($entityClassName);
			if ($classMetadata === null) {
				continue;
			}

			if ($classMetadata->hasField($fieldName) || $classMetadata->hasAssociation($fieldName)) {
				return true;
			}
		}

		return false;
	}

	private function classify(string $word): string
	{
		return lcfirst(str_replace([' ', '_', '-'], '', ucwords($word, ' _-')));
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		$repositoryAncesor = $classReflection->getAncestorWithClassName(ObjectRepository::class);
		if ($repositoryAncesor === null) {
			$repositoryAncesor = $classReflection->getAncestorWithClassName(ObjectRepository::class);
			if ($repositoryAncesor === null) {
				throw new ShouldNotHappenException();
			}
		}

		$templateTypeMap = $repositoryAncesor->getActiveTemplateTypeMap();
		$entityClassType = $templateTypeMap->getType('TEntityClass');
		if ($entityClassType === null) {
			throw new ShouldNotHappenException();
		}

		if (
			strpos($methodName, 'findBy') === 0
		) {
			$returnType = new ArrayType(new IntegerType(), $entityClassType);
		} elseif (
			strpos($methodName, 'findOneBy') === 0
		) {
			$returnType = TypeCombinator::union($entityClassType, new NullType());
		} elseif (
			strpos($methodName, 'countBy') === 0
		) {
			$returnType = new IntegerType();
		} else {
			throw new ShouldNotHappenException();
		}

		return new MagicRepositoryMethodReflection($repositoryAncesor, $methodName, $returnType);
	}

}
