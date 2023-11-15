<?php declare(strict_types = 1);

namespace PHPStan\Rules\Doctrine\ORM;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class EntityNotFinalRule implements Rule
{

	/** @var ObjectMetadataResolver */
	private $objectMetadataResolver;

	public function __construct(ObjectMetadataResolver $objectMetadataResolver)
	{
		$this->objectMetadataResolver = $objectMetadataResolver;
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $scope->getClassReflection();
		if ($classReflection === null) {
			throw new ShouldNotHappenException();
		}
		if (!$classReflection->isFinalByKeyword()) {
			return [];
		}

		if ($this->objectMetadataResolver->isTransient($classReflection->getName())) {
			return [];
		}

		$metadata = $this->objectMetadataResolver->getClassMetadata($classReflection->getName());
		if ($metadata !== null && $metadata->isEmbeddedClass === true) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Entity class %s is final which can cause problems with proxies.',
				$classReflection->getDisplayName()
			))->identifier('doctrine.finalEntity')->build(),
		];
	}

}
