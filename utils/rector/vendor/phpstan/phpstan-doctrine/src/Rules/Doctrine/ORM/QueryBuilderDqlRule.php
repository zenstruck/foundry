<?php declare(strict_types = 1);

namespace PHPStan\Rules\Doctrine\ORM;

use AssertionError;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Doctrine\DoctrineTypeUtils;
use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeUtils;
use Throwable;
use function count;
use function sprintf;
use function strpos;

/**
 * @implements Rule<Node\Expr\MethodCall>
 */
class QueryBuilderDqlRule implements Rule
{

	/** @var ObjectMetadataResolver */
	private $objectMetadataResolver;

	/** @var bool */
	private $reportDynamicQueryBuilders;

	public function __construct(
		ObjectMetadataResolver $objectMetadataResolver,
		bool $reportDynamicQueryBuilders
	)
	{
		$this->objectMetadataResolver = $objectMetadataResolver;
		$this->reportDynamicQueryBuilders = $reportDynamicQueryBuilders;
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

		if ($node->name->toLowerString() !== 'getquery') {
			return [];
		}

		$calledOnType = $scope->getType($node->var);
		$queryBuilderTypes = DoctrineTypeUtils::getQueryBuilderTypes($calledOnType);
		if (count($queryBuilderTypes) === 0) {
			if (
				$this->reportDynamicQueryBuilders
				&& (new ObjectType('Doctrine\ORM\QueryBuilder'))->isSuperTypeOf($calledOnType)->yes()
			) {
				return [
					RuleErrorBuilder::message('Could not analyse QueryBuilder with unknown beginning.')
						->identifier('doctrine.queryBuilderDynamic')
						->build(),
				];
			}
			return [];
		}

		try {
			$dqlType = $scope->getType(new MethodCall($node, new Node\Identifier('getDQL'), []));
		} catch (Throwable $e) {
			return [
				RuleErrorBuilder::message(sprintf('Internal error: %s', $e->getMessage()))
					->nonIgnorable()
					->identifier('doctrine.internalError')
					->build(),
			];
		}

		$dqls = TypeUtils::getConstantStrings($dqlType);
		if (count($dqls) === 0) {
			if ($this->reportDynamicQueryBuilders) {
				return [
					RuleErrorBuilder::message('Could not analyse QueryBuilder with dynamic arguments.')
						->identifier('doctrine.queryBuilderDynamicArgument')
						->build(),
				];
			}
			return [];
		}

		$objectManager = $this->objectMetadataResolver->getObjectManager();
		if ($objectManager === null) {
			return [];
		}

		$entityManagerInterface = 'Doctrine\ORM\EntityManagerInterface';
		if (!$objectManager instanceof $entityManagerInterface) {
			return [];
		}

		/** @var EntityManagerInterface $objectManager */
		$objectManager = $objectManager;

		$messages = [];
		foreach ($dqls as $dql) {
			try {
				$objectManager->createQuery($dql->getValue())->getAST();
			} catch (QueryException $e) {
				$message = sprintf('QueryBuilder: %s', $e->getMessage());
				if (strpos($e->getMessage(), '[Syntax Error]') === 0) {
					$message .= sprintf("\nDQL: %s", $dql->getValue());
				}

				$messages[] = RuleErrorBuilder::message($message)
					->identifier('doctrine.dql')
					->build();
			} catch (AssertionError $e) {
				continue;
			}
		}

		return $messages;
	}

}
