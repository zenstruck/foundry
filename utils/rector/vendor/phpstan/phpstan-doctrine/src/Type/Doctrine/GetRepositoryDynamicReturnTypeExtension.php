<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\ObjectRepository;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

class GetRepositoryDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	/** @var ReflectionProvider */
	private $reflectionProvider;

	/** @var string|null */
	private $repositoryClass;

	/** @var string|null */
	private $ormRepositoryClass;

	/** @var string|null */
	private $odmRepositoryClass;

	/** @var string */
	private $managerClass;

	/** @var ObjectMetadataResolver */
	private $metadataResolver;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		?string $repositoryClass,
		?string $ormRepositoryClass,
		?string $odmRepositoryClass,
		string $managerClass,
		ObjectMetadataResolver $metadataResolver
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->repositoryClass = $repositoryClass;
		$this->ormRepositoryClass = $ormRepositoryClass;
		$this->odmRepositoryClass = $odmRepositoryClass;
		$this->managerClass = $managerClass;
		$this->metadataResolver = $metadataResolver;
	}

	public function getClass(): string
	{
		return $this->managerClass;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getRepository';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): Type
	{
		$calledOnType = $scope->getType($methodCall->var);
		if ((new ObjectType(DocumentManager::class))->isSuperTypeOf($calledOnType)->yes()) {
			$defaultRepositoryClass = $this->odmRepositoryClass ?? $this->repositoryClass ?? DocumentRepository::class;
		} else {
			$defaultRepositoryClass = $this->ormRepositoryClass ?? $this->repositoryClass ?? EntityRepository::class;
		}
		if (count($methodCall->getArgs()) === 0) {
			return new GenericObjectType(
				$defaultRepositoryClass,
				[new ObjectWithoutClassType()]
			);
		}
		$argType = $scope->getType($methodCall->getArgs()[0]->value);
		if (!$argType->isClassStringType()->yes()) {
			return $this->getDefaultReturnType($scope, $methodCall->getArgs(), $methodReflection, $defaultRepositoryClass);
		}

		$classType = $argType->getClassStringObjectType();
		$objectNames = $classType->getObjectClassNames();

		if (count($objectNames) === 0) {
			return new GenericObjectType(
				$defaultRepositoryClass,
				[$classType]
			);
		}

		$repositoryTypes = [];
		foreach ($objectNames as $objectName) {
			try {
				$repositoryClass = $this->getRepositoryClass($objectName, $defaultRepositoryClass);
			} catch (\Doctrine\Persistence\Mapping\MappingException | MappingException | AnnotationException $e) {
				return $this->getDefaultReturnType($scope, $methodCall->getArgs(), $methodReflection, $defaultRepositoryClass);
			}

			$repositoryTypes[] = new GenericObjectType($repositoryClass, [$classType]);
		}

		return TypeCombinator::union(...$repositoryTypes);
	}

	/**
	 * @param Arg[] $args
	 */
	private function getDefaultReturnType(Scope $scope, array $args, MethodReflection $methodReflection, string $defaultRepositoryClass): Type
	{
		$defaultType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$args,
			$methodReflection->getVariants()
		)->getReturnType();
		$entity = $defaultType->getTemplateType(ObjectRepository::class, 'TEntityClass');
		if (!$entity instanceof ErrorType) {
			return new GenericObjectType(
				$defaultRepositoryClass,
				[$entity]
			);
		}

		return $defaultType;
	}

	private function getRepositoryClass(string $className, string $defaultRepositoryClass): string
	{
		if (!$this->reflectionProvider->hasClass($className)) {
			return $defaultRepositoryClass;
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		if ($classReflection->isInterface() || $classReflection->isTrait()) {
			return $defaultRepositoryClass;
		}

		$metadata = $this->metadataResolver->getClassMetadata($classReflection->getName());
		if ($metadata !== null) {
			return $metadata->customRepositoryClassName ?? $defaultRepositoryClass;
		}

		$objectManager = $this->metadataResolver->getObjectManager();
		if ($objectManager === null) {
			return $defaultRepositoryClass;
		}

		$metadata = $objectManager->getClassMetadata($classReflection->getName());
		$odmMetadataClass = 'Doctrine\ODM\MongoDB\Mapping\ClassMetadata';
		if ($metadata instanceof $odmMetadataClass) {
			/** @var ClassMetadata<object> $odmMetadata */
			$odmMetadata = $metadata;
			return $odmMetadata->customRepositoryClassName ?? $defaultRepositoryClass;
		}

		return $defaultRepositoryClass;
	}

}
