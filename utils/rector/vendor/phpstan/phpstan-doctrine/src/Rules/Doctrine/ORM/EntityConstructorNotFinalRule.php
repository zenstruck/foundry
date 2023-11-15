<?php declare(strict_types = 1);

namespace PHPStan\Rules\Doctrine\ORM;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use function sprintf;

/**
 * @implements Rule<ClassMethod>
 */
class EntityConstructorNotFinalRule implements Rule
{

	/** @var ObjectMetadataResolver */
	private $objectMetadataResolver;

	public function __construct(ObjectMetadataResolver $objectMetadataResolver)
	{
		$this->objectMetadataResolver = $objectMetadataResolver;
	}

	public function getNodeType(): string
	{
		return ClassMethod::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->name->name !== '__construct') {
			return [];
		}

		if (!$node->isFinal()) {
			return [];
		}

		$classReflection = $scope->getClassReflection();
		if ($classReflection === null) {
			throw new ShouldNotHappenException();
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
				'Constructor of class %s is final which can cause problems with proxies.',
				$classReflection->getDisplayName()
			))->identifier('doctrine.finalConstructor')->build(),
		];
	}

}
