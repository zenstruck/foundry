<?php declare(strict_types = 1);

namespace PHPStan\Rules\Doctrine\ORM;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\Mapping\MappingException;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use ReflectionException;

/**
 * @implements Rule<InClassNode>
 */
class EntityMappingExceptionRule implements Rule
{

	/** @var ObjectMetadataResolver */
	private $objectMetadataResolver;

	public function __construct(
		ObjectMetadataResolver $objectMetadataResolver
	)
	{
		$this->objectMetadataResolver = $objectMetadataResolver;
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$class = $scope->getClassReflection();
		if ($class === null) {
			return [];
		}

		$objectManager = $this->objectMetadataResolver->getObjectManager();
		if ($objectManager === null) {
			return [];
		}

		$className = $class->getName();
		try {
			if ($objectManager->getMetadataFactory()->isTransient($className)) {
				return [];
			}
		} catch (ReflectionException $e) {
			return [];
		}

		try {
			$objectManager->getClassMetadata($className);
		} catch (\Doctrine\Persistence\Mapping\MappingException | MappingException | AnnotationException $e) {
			return [
				RuleErrorBuilder::message($e->getMessage())
					->nonIgnorable()
					->identifier('doctrine.mapping')
					->build(),
			];
		}

		return [];
	}

}
