<?php declare(strict_types = 1);

namespace PHPStan\Rules\Doctrine\ORM;

use Doctrine\Persistence\ObjectRepository;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use PHPStan\Type\VerbosityLevel;
use function count;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Node\Expr\MethodCall>
 */
class RepositoryMethodCallRule implements Rule
{

	/** @var ObjectMetadataResolver */
	private $objectMetadataResolver;

	public function __construct(ObjectMetadataResolver $objectMetadataResolver)
	{
		$this->objectMetadataResolver = $objectMetadataResolver;
	}

	public function getNodeType(): string
	{
		return Node\Expr\MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!isset($node->getArgs()[0])) {
			return [];
		}
		$argType = $scope->getType($node->getArgs()[0]->value);
		$calledOnType = $scope->getType($node->var);
		$entityClassType = $calledOnType->getTemplateType(ObjectRepository::class, 'TEntityClass');

		/** @var list<class-string> $entityClassNames */
		$entityClassNames = $entityClassType->getObjectClassNames();
		if (count($entityClassNames) !== 1) {
			return [];
		}

		$methodNameIdentifier = $node->name;
		if (!$methodNameIdentifier instanceof Node\Identifier) {
			return [];
		}

		$methodName = $methodNameIdentifier->toString();
		if (!in_array($methodName, [
			'findBy',
			'findOneBy',
			'count',
		], true)) {
			return [];
		}

		$classMetadata = $this->objectMetadataResolver->getClassMetadata($entityClassNames[0]);
		if ($classMetadata === null) {
			return [];
		}

		$messages = [];
		foreach ($argType->getConstantArrays() as $constantArray) {
			foreach ($constantArray->getKeyTypes() as $keyType) {
				foreach ($keyType->getConstantStrings() as $fieldName) {
					if (
						$classMetadata->hasField($fieldName->getValue())
						|| $classMetadata->hasAssociation($fieldName->getValue())
					) {
						continue;
					}

					$messages[] = RuleErrorBuilder::message(sprintf(
						'Call to method %s::%s() - entity %s does not have a field named $%s.',
						$calledOnType->describe(VerbosityLevel::typeOnly()),
						$methodName,
						$entityClassNames[0],
						$fieldName->getValue()
					))->identifier(sprintf('doctrine.%sArgument', $methodName))->build();
				}
			}
		}

		return $messages;
	}

}
