<?php declare(strict_types = 1);

namespace PHPStan\Rules\Doctrine\ORM;

use AssertionError;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeUtils;
use function count;
use function sprintf;

/**
 * @implements Rule<Node\Expr\MethodCall>
 */
class DqlRule implements Rule
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
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}

		if (count($node->getArgs()) === 0) {
			return [];
		}

		$methodName = $node->name->toLowerString();
		if ($methodName !== 'createquery') {
			return [];
		}

		$calledOnType = $scope->getType($node->var);
		$entityManagerInterface = 'Doctrine\ORM\EntityManagerInterface';
		if (!(new ObjectType($entityManagerInterface))->isSuperTypeOf($calledOnType)->yes()) {
			return [];
		}

		$dqls = TypeUtils::getConstantStrings($scope->getType($node->getArgs()[0]->value));
		if (count($dqls) === 0) {
			return [];
		}

		$objectManager = $this->objectMetadataResolver->getObjectManager();
		if ($objectManager === null) {
			return [];
		}
		if (!$objectManager instanceof $entityManagerInterface) {
			return [];
		}

		/** @var EntityManagerInterface $objectManager */
		$objectManager = $objectManager;

		$messages = [];
		foreach ($dqls as $dql) {
			$query = $objectManager->createQuery($dql->getValue());
			try {
				$query->getAST();
			} catch (QueryException $e) {
				$messages[] = RuleErrorBuilder::message(sprintf('DQL: %s', $e->getMessage()))
					->identifier('doctrine.dql')
					->build();
			} catch (AssertionError $e) {
				continue;
			}
		}

		return $messages;
	}

}
