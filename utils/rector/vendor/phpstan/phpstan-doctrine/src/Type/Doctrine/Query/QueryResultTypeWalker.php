<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Query;

use BackedEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\AST\TypedExpression;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\SqlWalker;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\Doctrine\DescriptorNotRegisteredException;
use PHPStan\Type\Doctrine\DescriptorRegistry;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function array_map;
use function assert;
use function class_exists;
use function count;
use function floatval;
use function get_class;
use function gettype;
use function intval;
use function is_numeric;
use function is_object;
use function is_string;
use function serialize;
use function sprintf;
use function strtolower;
use function unserialize;

/**
 * QueryResultTypeWalker is a TreeWalker that uses a QueryResultTypeBuilder to build the result type of a Query
 *
 * It extends SqlkWalker because AST\Node::dispatch() accepts SqlWalker only
 *
 * @phpstan-type QueryComponent array{metadata: ClassMetadata<object>, parent: mixed, relation: ?array{orderBy: array<array-key, string>, indexBy: ?string, fieldName: string, targetEntity: string, sourceEntity: string, isOwningSide: bool, mappedBy: string, type: int}, map: mixed, nestingLevel: int, token: mixed}
 */
class QueryResultTypeWalker extends SqlWalker
{

	private const HINT_TYPE_MAPPING = self::class . '::HINT_TYPE_MAPPING';

	private const HINT_DESCRIPTOR_REGISTRY = self::class . '::HINT_DESCRIPTOR_REGISTRY';

	/**
	 * Counter for generating unique scalar result.
	 *
	 * @var int
	 */
	private $scalarResultCounter = 1;

	/**
	 * Counter for generating indexes.
	 *
	 * @var int
	 */
	private $newObjectCounter = 0;

	/** @var Query<mixed> */
	private $query;

	/** @var EntityManagerInterface */
	private $em;

	/**
	 * Map of all components/classes that appear in the DQL query.
	 *
	 * @var array<array-key,QueryComponent> $queryComponents
	 */
	private $queryComponents;

	/** @var array<array-key,bool> */
	private $nullableQueryComponents;

	/** @var QueryResultTypeBuilder */
	private $typeBuilder;

	/** @var DescriptorRegistry */
	private $descriptorRegistry;

	/** @var bool */
	private $hasAggregateFunction;

	/** @var bool */
	private $hasGroupByClause;

	/**
	 * @param Query<mixed> $query
	 */
	public static function walk(Query $query, QueryResultTypeBuilder $typeBuilder, DescriptorRegistry $descriptorRegistry): void
	{
		$query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::class);
		$query->setHint(self::HINT_TYPE_MAPPING, $typeBuilder);
		$query->setHint(self::HINT_DESCRIPTOR_REGISTRY, $descriptorRegistry);

		$parser = new Parser($query);
		$parser->parse();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Query<mixed> $query
	 * @param ParserResult $parserResult
	 * @param array<QueryComponent> $queryComponents
	 */
	public function __construct($query, $parserResult, array $queryComponents)
	{
		$this->query = $query;
		$this->em = $query->getEntityManager();
		$this->queryComponents = $queryComponents;
		$this->nullableQueryComponents = [];
		$this->hasAggregateFunction = false;
		$this->hasGroupByClause = false;

		// The object is instantiated by Doctrine\ORM\Query\Parser, so receiving
		// dependencies through the constructor is not an option. Instead, we
		// receive the dependencies via query hints.

		$typeBuilder = $this->query->getHint(self::HINT_TYPE_MAPPING);

		if (!$typeBuilder instanceof QueryResultTypeBuilder) {
			throw new ShouldNotHappenException(sprintf(
				'Expected the query hint %s to contain a %s, but got a %s',
				self::HINT_TYPE_MAPPING,
				QueryResultTypeBuilder::class,
				is_object($typeBuilder) ? get_class($typeBuilder) : gettype($typeBuilder)
			));
		}

		$this->typeBuilder = $typeBuilder;

		$descriptorRegistry = $this->query->getHint(self::HINT_DESCRIPTOR_REGISTRY);

		if (!$descriptorRegistry instanceof DescriptorRegistry) {
			throw new ShouldNotHappenException(sprintf(
				'Expected the query hint %s to contain a %s, but got a %s',
				self::HINT_DESCRIPTOR_REGISTRY,
				DescriptorRegistry::class,
				is_object($descriptorRegistry) ? get_class($descriptorRegistry) : gettype($descriptorRegistry)
			));
		}

		$this->descriptorRegistry = $descriptorRegistry;

		parent::__construct($query, $parserResult, $queryComponents);
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkSelectStatement(AST\SelectStatement $AST)
	{
		$this->typeBuilder->setSelectQuery();
		$this->hasAggregateFunction = $this->hasAggregateFunction($AST);
		$this->hasGroupByClause = $AST->groupByClause !== null;

		$this->walkFromClause($AST->fromClause);

		foreach ($AST->selectClause->selectExpressions as $selectExpression) {
			assert($selectExpression instanceof AST\Node);

			$selectExpression->dispatch($this);
		}

		return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkUpdateStatement(AST\UpdateStatement $AST)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkDeleteStatement(AST\DeleteStatement $AST)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkEntityIdentificationVariable($identVariable)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkIdentificationVariable($identificationVariable, $fieldName = null)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkPathExpression($pathExpr)
	{
		$fieldName = $pathExpr->field;
		$dqlAlias = $pathExpr->identificationVariable;
		$qComp = $this->queryComponents[$dqlAlias];
		$class = $qComp['metadata'];

		assert($fieldName !== null);

		switch ($pathExpr->type) {
			case AST\PathExpression::TYPE_STATE_FIELD:
				[$typeName, $enumType] = $this->getTypeOfField($class, $fieldName);

				$nullable = $this->isQueryComponentNullable($dqlAlias)
					|| $class->isNullable($fieldName)
					|| $this->hasAggregateWithoutGroupBy();

				$fieldType = $this->resolveDatabaseInternalType($typeName, $enumType, $nullable);

				return $this->marshalType($fieldType);

			case AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION:
				if (isset($class->associationMappings[$fieldName]['inherited'])) {
					$newClassName = $class->associationMappings[$fieldName]['inherited'];
					$class = $this->em->getClassMetadata($newClassName);
				}

				$assoc = $class->associationMappings[$fieldName];

				if (
					!$assoc['isOwningSide']
					|| !isset($assoc['joinColumns'])
					|| count($assoc['joinColumns']) !== 1
				) {
					throw new ShouldNotHappenException();
				}

				$joinColumn = $assoc['joinColumns'][0];
				$assocClassName = $assoc['targetEntity'];

				$targetClass = $this->em->getClassMetadata($assocClassName);
				$identifierFieldNames = $targetClass->getIdentifierFieldNames();

				if (count($identifierFieldNames) !== 1) {
					throw new ShouldNotHappenException();
				}

				$targetFieldName = $identifierFieldNames[0];
				[$typeName, $enumType] = $this->getTypeOfField($targetClass, $targetFieldName);

				$nullable = ($joinColumn['nullable'] ?? true)
					|| $this->hasAggregateWithoutGroupBy();

				$fieldType = $this->resolveDatabaseInternalType($typeName, $enumType, $nullable);

				return $this->marshalType($fieldType);

			default:
				throw new ShouldNotHappenException();
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkSelectClause($selectClause)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkFromClause($fromClause)
	{
		foreach ($fromClause->identificationVariableDeclarations as $identificationVariableDecl) {
			assert($identificationVariableDecl instanceof AST\Node);

			$identificationVariableDecl->dispatch($this);
		}

		return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkIdentificationVariableDeclaration($identificationVariableDecl)
	{
		if ($identificationVariableDecl->indexBy !== null) {
			$identificationVariableDecl->indexBy->dispatch($this);
		}

		foreach ($identificationVariableDecl->joins as $join) {
			assert($join instanceof AST\Node);

			$join->dispatch($this);
		}

		return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkIndexBy($indexBy): void
	{
		$type = $this->unmarshalType($indexBy->singleValuedPathExpression->dispatch($this));
		$this->typeBuilder->setIndexedBy($type);
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkRangeVariableDeclaration($rangeVariableDeclaration)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkJoinAssociationDeclaration($joinAssociationDeclaration, $joinType = AST\Join::JOIN_TYPE_INNER, $condExpr = null)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkFunction($function)
	{
		switch (true) {
			case $function instanceof AST\Functions\AvgFunction:
			case $function instanceof AST\Functions\MaxFunction:
			case $function instanceof AST\Functions\MinFunction:
			case $function instanceof AST\Functions\SumFunction:
			case $function instanceof AST\Functions\CountFunction:
				return $function->getSql($this);

			case $function instanceof AST\Functions\AbsFunction:
				$exprType = $this->unmarshalType($function->simpleArithmeticExpression->dispatch($this));

				$type = TypeCombinator::union(
					IntegerRangeType::fromInterval(0, null),
					new FloatType()
				);

				if (TypeCombinator::containsNull($exprType)) {
					$type = TypeCombinator::addNull($type);
				}

				return $this->marshalType($type);

			case $function instanceof AST\Functions\BitAndFunction:
			case $function instanceof AST\Functions\BitOrFunction:
				$firstExprType = $this->unmarshalType($function->firstArithmetic->dispatch($this));
				$secondExprType = $this->unmarshalType($function->secondArithmetic->dispatch($this));

				$type = IntegerRangeType::fromInterval(0, null);
				if (TypeCombinator::containsNull($firstExprType) || TypeCombinator::containsNull($secondExprType)) {
					$type = TypeCombinator::addNull($type);
				}

				return $this->marshalType($type);

			case $function instanceof AST\Functions\ConcatFunction:
				$hasNull = false;

				foreach ($function->concatExpressions as $expr) {
					$type = $this->unmarshalType($expr->dispatch($this));
					$hasNull = $hasNull || TypeCombinator::containsNull($type);
				}

				$type = new StringType();
				if ($hasNull) {
					$type = TypeCombinator::addNull($type);
				}

				return $this->marshalType($type);

			case $function instanceof AST\Functions\CurrentDateFunction:
			case $function instanceof AST\Functions\CurrentTimeFunction:
			case $function instanceof AST\Functions\CurrentTimestampFunction:
				return $this->marshalType(new StringType());

			case $function instanceof AST\Functions\DateAddFunction:
			case $function instanceof AST\Functions\DateSubFunction:
				$dateExprType = $this->unmarshalType($function->firstDateExpression->dispatch($this));
				$intervalExprType = $this->unmarshalType($function->intervalExpression->dispatch($this));

				$type = new StringType();
				if (TypeCombinator::containsNull($dateExprType) || TypeCombinator::containsNull($intervalExprType)) {
					$type = TypeCombinator::addNull($type);
				}

				return $this->marshalType($type);

			case $function instanceof AST\Functions\DateDiffFunction:
				$date1ExprType = $this->unmarshalType($function->date1->dispatch($this));
				$date2ExprType = $this->unmarshalType($function->date2->dispatch($this));

				$type = TypeCombinator::union(
					new IntegerType(),
					new FloatType()
				);
				if (TypeCombinator::containsNull($date1ExprType) || TypeCombinator::containsNull($date2ExprType)) {
					$type = TypeCombinator::addNull($type);
				}

				return $this->marshalType($type);

			case $function instanceof AST\Functions\LengthFunction:
				$stringPrimaryType = $this->unmarshalType($function->stringPrimary->dispatch($this));

				$type = IntegerRangeType::fromInterval(0, null);
				if (TypeCombinator::containsNull($stringPrimaryType)) {
					$type = TypeCombinator::addNull($type);
				}

				return $this->marshalType($type);

			case $function instanceof AST\Functions\LocateFunction:
				$firstExprType = $this->unmarshalType($function->firstStringPrimary->dispatch($this));
				$secondExprType = $this->unmarshalType($function->secondStringPrimary->dispatch($this));

				$type = IntegerRangeType::fromInterval(0, null);
				if (TypeCombinator::containsNull($firstExprType) || TypeCombinator::containsNull($secondExprType)) {
					$type = TypeCombinator::addNull($type);
				}

				return $this->marshalType($type);

			case $function instanceof AST\Functions\LowerFunction:
			case $function instanceof AST\Functions\TrimFunction:
			case $function instanceof AST\Functions\UpperFunction:
				$stringPrimaryType = $this->unmarshalType($function->stringPrimary->dispatch($this));

				$type = new StringType();
				if (TypeCombinator::containsNull($stringPrimaryType)) {
					$type = TypeCombinator::addNull($type);
				}

				return $this->marshalType($type);

			case $function instanceof AST\Functions\ModFunction:
				$firstExprType = $this->unmarshalType($function->firstSimpleArithmeticExpression->dispatch($this));
				$secondExprType = $this->unmarshalType($function->secondSimpleArithmeticExpression->dispatch($this));

				$type = IntegerRangeType::fromInterval(0, null);
				if (TypeCombinator::containsNull($firstExprType) || TypeCombinator::containsNull($secondExprType)) {
					$type = TypeCombinator::addNull($type);
				}

				if ((new ConstantIntegerType(0))->isSuperTypeOf($secondExprType)->maybe()) {
					// MOD(x, 0) returns NULL
					$type = TypeCombinator::addNull($type);
				}

				return $this->marshalType($type);

			case $function instanceof AST\Functions\SqrtFunction:
				$exprType = $this->unmarshalType($function->simpleArithmeticExpression->dispatch($this));

				$type = new FloatType();
				if (TypeCombinator::containsNull($exprType)) {
					$type = TypeCombinator::addNull($type);
				}

				return $this->marshalType($type);

			case $function instanceof AST\Functions\SubstringFunction:
				$stringType = $this->unmarshalType($function->stringPrimary->dispatch($this));
				$firstExprType = $this->unmarshalType($function->firstSimpleArithmeticExpression->dispatch($this));

				if ($function->secondSimpleArithmeticExpression !== null) {
					$secondExprType = $this->unmarshalType($function->secondSimpleArithmeticExpression->dispatch($this));
				} else {
					$secondExprType = new IntegerType();
				}

				$type = new StringType();
				if (TypeCombinator::containsNull($stringType) || TypeCombinator::containsNull($firstExprType) || TypeCombinator::containsNull($secondExprType)) {
					$type = TypeCombinator::addNull($type);
				}

				return $this->marshalType($type);

			case $function instanceof AST\Functions\IdentityFunction:
				$dqlAlias = $function->pathExpression->identificationVariable;
				$assocField = $function->pathExpression->field;
				$queryComp = $this->queryComponents[$dqlAlias];
				$class = $queryComp['metadata'];
				$assoc = $class->associationMappings[$assocField];
				$assocClassName = $assoc['targetEntity'];
				$targetClass = $this->em->getClassMetadata($assocClassName);

				if ($function->fieldMapping === null) {
					$identifierFieldNames = $targetClass->getIdentifierFieldNames();
					if (count($identifierFieldNames) === 0) {
						throw new ShouldNotHappenException();
					}

					$targetFieldName = $identifierFieldNames[0];
				} else {
					$targetFieldName = $function->fieldMapping;
				}

				$fieldMapping = $targetClass->fieldMappings[$targetFieldName] ?? null;
				if ($fieldMapping === null) {
					return $this->marshalType(new MixedType());
				}

				[$typeName, $enumType] = $this->getTypeOfField($targetClass, $targetFieldName);

				if (!isset($assoc['joinColumns'])) {
					return $this->marshalType(new MixedType());
				}

				$joinColumn = null;

				foreach ($assoc['joinColumns'] as $item) {
					if ($item['referencedColumnName'] === $fieldMapping['columnName']) {
						$joinColumn = $item;
						break;
					}
				}

				if ($joinColumn === null) {
					return $this->marshalType(new MixedType());
				}

				$nullable = ($joinColumn['nullable'] ?? true)
					|| $this->isQueryComponentNullable($dqlAlias)
					|| $this->hasAggregateWithoutGroupBy();

				$fieldType = $this->resolveDatabaseInternalType($typeName, $enumType, $nullable);

				return $this->marshalType($fieldType);

			default:
				return $this->marshalType(new MixedType());
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkOrderByClause($orderByClause)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkOrderByItem($orderByItem)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkHavingClause($havingClause)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkJoin($join)
	{
		$joinType = $join->joinType;
		$joinDeclaration = $join->joinAssociationDeclaration;

		switch (true) {
			case $joinDeclaration instanceof AST\RangeVariableDeclaration:
				$dqlAlias = $joinDeclaration->aliasIdentificationVariable;

				$this->nullableQueryComponents[$dqlAlias] = $joinType === AST\Join::JOIN_TYPE_LEFT || $joinType === AST\Join::JOIN_TYPE_LEFTOUTER;

				break;
			case $joinDeclaration instanceof AST\JoinAssociationDeclaration:
				$dqlAlias = $joinDeclaration->aliasIdentificationVariable;

				$this->nullableQueryComponents[$dqlAlias] = $joinType === AST\Join::JOIN_TYPE_LEFT || $joinType === AST\Join::JOIN_TYPE_LEFTOUTER;

				break;
		}

		return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkCoalesceExpression($coalesceExpression)
	{
		$expressionTypes = [];
		$allTypesContainNull = true;

		foreach ($coalesceExpression->scalarExpressions as $expression) {
			if (!$expression instanceof AST\Node) {
				$expressionTypes[] = new MixedType();
				continue;
			}

			$type = $this->unmarshalType($expression->dispatch($this));
			$allTypesContainNull = $allTypesContainNull && TypeCombinator::containsNull($type);

			$expressionTypes[] = $type;
		}

		$type = TypeCombinator::union(...$expressionTypes);

		if (!$allTypesContainNull) {
			$type = TypeCombinator::removeNull($type);
		}

		return $this->marshalType($type);
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkNullIfExpression($nullIfExpression)
	{
		$firstExpression = $nullIfExpression->firstExpression;

		if (!$firstExpression instanceof AST\Node) {
			return $this->marshalType(new MixedType());
		}

		$firstType = $this->unmarshalType($firstExpression->dispatch($this));

		// NULLIF() returns the first expression or NULL
		$type = TypeCombinator::addNull($firstType);

		return $this->marshalType($type);
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkGeneralCaseExpression(AST\GeneralCaseExpression $generalCaseExpression)
	{
		$whenClauses = $generalCaseExpression->whenClauses;
		$elseScalarExpression = $generalCaseExpression->elseScalarExpression;
		$types = [];

		foreach ($whenClauses as $clause) {
			if (!$clause instanceof AST\WhenClause) {
				$types[] = new MixedType();
				continue;
			}

			$thenScalarExpression = $clause->thenScalarExpression;
			if (!$thenScalarExpression instanceof AST\Node) {
				$types[] = new MixedType();
				continue;
			}

			$types[] = $this->unmarshalType(
				$thenScalarExpression->dispatch($this)
			);
		}

		if ($elseScalarExpression instanceof AST\Node) {
			$types[] = $this->unmarshalType(
				$elseScalarExpression->dispatch($this)
			);
		}

		$type = TypeCombinator::union(...$types);

		return $this->marshalType($type);
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkSimpleCaseExpression($simpleCaseExpression)
	{
		$whenClauses = $simpleCaseExpression->simpleWhenClauses;
		$elseScalarExpression = $simpleCaseExpression->elseScalarExpression;
		$types = [];

		foreach ($whenClauses as $clause) {
			if (!$clause instanceof AST\SimpleWhenClause) {
				$types[] = new MixedType();
				continue;
			}

			$thenScalarExpression = $clause->thenScalarExpression;
			if (!$thenScalarExpression instanceof AST\Node) {
				$types[] = new MixedType();
				continue;
			}

			$types[] = $this->unmarshalType(
				$thenScalarExpression->dispatch($this)
			);
		}

		if ($elseScalarExpression instanceof AST\Node) {
			$types[] = $this->unmarshalType(
				$elseScalarExpression->dispatch($this)
			);
		}

		$type = TypeCombinator::union(...$types);

		return $this->marshalType($type);
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkSelectExpression($selectExpression)
	{
		$expr = $selectExpression->expression;
		$hidden = $selectExpression->hiddenAliasResultVariable;

		if ($hidden) {
			return '';
		}

		if (is_string($expr)) {
			$dqlAlias = $expr;
			$queryComp = $this->queryComponents[$dqlAlias];
			$class = $queryComp['metadata'];
			$resultAlias = $selectExpression->fieldIdentificationVariable ?? $dqlAlias;

			if ($queryComp['parent'] !== null) {
				return '';
			}

			$type = new ObjectType($class->name);

			if ($this->isQueryComponentNullable($dqlAlias) || $this->hasAggregateWithoutGroupBy()) {
				$type = TypeCombinator::addNull($type);
			}

			$this->typeBuilder->addEntity($resultAlias, $type, $selectExpression->fieldIdentificationVariable);

			return '';
		}

		if ($expr instanceof AST\PathExpression) {
			assert($expr->type === AST\PathExpression::TYPE_STATE_FIELD);

			$fieldName = $expr->field;

			assert($fieldName !== null);

			$resultAlias = $selectExpression->fieldIdentificationVariable ?? $fieldName;

			$dqlAlias = $expr->identificationVariable;
			$qComp = $this->queryComponents[$dqlAlias];
			$class = $qComp['metadata'];

			[$typeName, $enumType] = $this->getTypeOfField($class, $fieldName);

			$nullable = $this->isQueryComponentNullable($dqlAlias)
				|| $class->isNullable($fieldName)
				|| $this->hasAggregateWithoutGroupBy();

			$type = $this->resolveDoctrineType($typeName, $enumType, $nullable);

			$this->typeBuilder->addScalar($resultAlias, $type);

			return '';
		}

		if ($expr instanceof AST\NewObjectExpression) {
			$resultAlias = $selectExpression->fieldIdentificationVariable ?? $this->newObjectCounter++;

			$type = $this->unmarshalType($this->walkNewObject($expr));
			$this->typeBuilder->addNewObject($resultAlias, $type);

			return '';
		}

		if ($expr instanceof AST\Node) {
			$resultAlias = $selectExpression->fieldIdentificationVariable ?? $this->scalarResultCounter++;
			$type = $this->unmarshalType($expr->dispatch($this));

			if (class_exists(TypedExpression::class) && $expr instanceof TypedExpression) {
				$enforcedType = $this->resolveDoctrineType($expr->getReturnType()->getName());
				$type = TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($enforcedType): Type {
					if ($type instanceof UnionType || $type instanceof IntersectionType) {
						return $traverse($type);
					}
					if ($type instanceof NullType) {
						return $type;
					}
					if ($enforcedType->accepts($type, true)->yes()) {
						return $type;
					}
					if ($enforcedType instanceof StringType) {
						if ($type instanceof IntegerType || $type instanceof FloatType) {
							return TypeCombinator::union($type->toString(), $type);
						}
						if ($type instanceof BooleanType) {
							return TypeCombinator::union($type->toInteger()->toString(), $type);
						}
					}
					return $enforcedType;
				});
			} else {
				// Expressions default to Doctrine's StringType, whose
				// convertToPHPValue() is a no-op. So the actual type depends on
				// the driver and PHP version.
				// Here we assume that the value may or may not be casted to
				// string by the driver.
				$type = TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
					if ($type instanceof UnionType || $type instanceof IntersectionType) {
						return $traverse($type);
					}
					if ($type instanceof IntegerType || $type instanceof FloatType) {
						return TypeCombinator::union($type->toString(), $type);
					}
					if ($type instanceof BooleanType) {
						return TypeCombinator::union($type->toInteger()->toString(), $type);
					}
					return $traverse($type);
				});
			}

			$this->typeBuilder->addScalar($resultAlias, $type);

			return '';
		}

		return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkQuantifiedExpression($qExpr)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkSubselect($subselect)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkSubselectFromClause($subselectFromClause)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkSimpleSelectClause($simpleSelectClause)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkParenthesisExpression(AST\ParenthesisExpression $parenthesisExpression)
	{
		return $parenthesisExpression->expression->dispatch($this);
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkNewObject($newObjectExpression, $newObjectResultAlias = null)
	{
		for ($i = 0; $i < count($newObjectExpression->args); $i++) {
			$this->scalarResultCounter++;
		}

		$type = new ObjectType($newObjectExpression->className);

		return $this->marshalType($type);
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkSimpleSelectExpression($simpleSelectExpression)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkAggregateExpression($aggExpression)
	{
		switch ($aggExpression->functionName) {
			case 'MAX':
			case 'MIN':
			case 'AVG':
			case 'SUM':
				$type = $this->unmarshalType(
					$aggExpression->pathExpression->dispatch($this)
				);

				return $this->marshalType(TypeCombinator::addNull($type));

			case 'COUNT':
				return $this->marshalType(IntegerRangeType::fromInterval(0, null));

			default:
				return $this->marshalType(new MixedType());
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkGroupByClause($groupByClause)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkGroupByItem($groupByItem)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkDeleteClause(AST\DeleteClause $deleteClause)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkUpdateClause($updateClause)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkUpdateItem($updateItem)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkWhereClause($whereClause)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkConditionalExpression($condExpr)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkConditionalTerm($condTerm)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkConditionalFactor($factor)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkConditionalPrimary($primary)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkExistsExpression($existsExpr)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkCollectionMemberExpression($collMemberExpr)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkEmptyCollectionComparisonExpression($emptyCollCompExpr)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkNullComparisonExpression($nullCompExpr)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkInExpression($inExpr)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkInstanceOfExpression($instanceOfExpr)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkInParameter($inParam)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkLiteral($literal)
	{
		switch ($literal->type) {
			case AST\Literal::STRING:
				$value = $literal->value;
				assert(is_string($value));
				$type = new ConstantStringType($value);
				break;

			case AST\Literal::BOOLEAN:
				$value = strtolower($literal->value) === 'true' ? 1 : 0;
				$type = new ConstantIntegerType($value);
				break;

			case AST\Literal::NUMERIC:
				$value = $literal->value;
				assert(is_numeric($value));

				if (floatval(intval($value)) === floatval($value)) {
					$type = new ConstantIntegerType((int) $value);
				} else {
					$type = new ConstantFloatType((float) $value);
				}

				break;

			default:
				$type = new MixedType();
				break;
		}

		return $this->marshalType($type);
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkBetweenExpression($betweenExpr)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkLikeExpression($likeExpr)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkStateFieldPathExpression($stateFieldPathExpression)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkComparisonExpression($compExpr)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkInputParameter($inputParam)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkArithmeticExpression($arithmeticExpr)
	{
		if ($arithmeticExpr->simpleArithmeticExpression !== null) {
			return $arithmeticExpr->simpleArithmeticExpression->dispatch($this);
		}

		if ($arithmeticExpr->subselect !== null) {
			return $arithmeticExpr->subselect->dispatch($this);
		}

		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkSimpleArithmeticExpression($simpleArithmeticExpr)
	{
		$types = [];

		foreach ($simpleArithmeticExpr->arithmeticTerms as $term) {
			if (!$term instanceof AST\Node) {
				// Skip '+' or '-'
				continue;
			}
			$type = $this->unmarshalType($this->walkArithmeticPrimary($term));
			$types[] = TypeUtils::generalizeType($type, GeneralizePrecision::lessSpecific());
		}

		$type = TypeCombinator::union(...$types);
		$type = $this->toNumericOrNull($type);

		return $this->marshalType($type);
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkArithmeticTerm($term)
	{
		if (!$term instanceof AST\ArithmeticTerm) {
			return $this->marshalType(new MixedType());
		}

		$types = [];

		foreach ($term->arithmeticFactors as $factor) {
			if (!$factor instanceof AST\Node) {
				// Skip '*' or '/'
				continue;
			}
			$type = $this->unmarshalType($this->walkArithmeticPrimary($factor));
			$types[] = TypeUtils::generalizeType($type, GeneralizePrecision::lessSpecific());
		}

		$type = TypeCombinator::union(...$types);
		$type = $this->toNumericOrNull($type);

		return $this->marshalType($type);
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkArithmeticFactor($factor)
	{
		if (!$factor instanceof AST\ArithmeticFactor) {
			return $this->marshalType(new MixedType());
		}

		$primary = $factor->arithmeticPrimary;

		$type = $this->unmarshalType($this->walkArithmeticPrimary($primary));
		$type = TypeUtils::generalizeType($type, GeneralizePrecision::lessSpecific());

		return $this->marshalType($type);
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkArithmeticPrimary($primary)
	{
		// ResultVariable (TODO)
		if (is_string($primary)) {
			return $this->marshalType(new MixedType());
		}

		if ($primary instanceof AST\Node) {
			return $primary->dispatch($this);
		}

		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkStringPrimary($stringPrimary)
	{
		return $this->marshalType(new MixedType());
	}

	/**
	 * {@inheritdoc}
	 */
	public function walkResultVariable($resultVariable)
	{
		return $this->marshalType(new MixedType());
	}

	private function unmarshalType(string $marshalledType): Type
	{
		$type = unserialize($marshalledType);

		assert($type instanceof Type);

		return $type;
	}

	private function marshalType(Type $type): string
	{
		// TreeWalker methods are supposed to return string, so we need to
		// marshal the types in strings
		return serialize($type);
	}

	private function isQueryComponentNullable(string $dqlAlias): bool
	{
		return $this->nullableQueryComponents[$dqlAlias] ?? false;
	}

	/**
	 * @param ClassMetadataInfo<object> $class
	 * @return array{string, ?class-string<BackedEnum>} Doctrine type name and enum type of field
	 */
	private function getTypeOfField(ClassMetadataInfo $class, string $fieldName): array
	{
		assert(isset($class->fieldMappings[$fieldName]));

		$metadata = $class->fieldMappings[$fieldName];

		$type = $metadata['type'];
		$enumType = $metadata['enumType'] ?? null;

		if (!is_string($enumType) || !class_exists($enumType)) {
			$enumType = null;
		}

		return [$type, $enumType];
	}

	/** @param ?class-string<BackedEnum> $enumType */
	private function resolveDoctrineType(string $typeName, ?string $enumType = null, bool $nullable = false): Type
	{
		if ($enumType !== null) {
			$type = new ObjectType($enumType);
		} else {
			try {
				$type = $this->descriptorRegistry
					->get($typeName)
					->getWritableToPropertyType();
				if ($type instanceof NeverType) {
					$type = new MixedType();
				}
			} catch (DescriptorNotRegisteredException $e) {
				$type = new MixedType();
			}
		}

		if ($nullable) {
			$type = TypeCombinator::addNull($type);
		}

		return $type;
	}

	/** @param ?class-string<BackedEnum> $enumType */
	private function resolveDatabaseInternalType(string $typeName, ?string $enumType = null, bool $nullable = false): Type
	{
		try {
			$type = $this->descriptorRegistry
				->get($typeName)
				->getDatabaseInternalType();
		} catch (DescriptorNotRegisteredException $e) {
			$type = new MixedType();
		}

		if ($enumType !== null) {
			$enumTypes = array_map(static function ($enumType) {
				return ConstantTypeHelper::getTypeFromValue($enumType->value);
			}, $enumType::cases());
			$enumType = TypeCombinator::union(...$enumTypes);
			$enumType = TypeCombinator::union($enumType, $enumType->toString());
			$type = TypeCombinator::intersect($enumType, $type);
		}

		if ($nullable) {
			$type = TypeCombinator::addNull($type);
		}

		return $type;
	}

	private function toNumericOrNull(Type $type): Type
	{
		return TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
			if ($type instanceof UnionType || $type instanceof IntersectionType) {
				return $traverse($type);
			}
			if ($type instanceof NullType || $type instanceof IntegerType) {
				return $type;
			}
			if ($type instanceof BooleanType) {
				return $type->toInteger();
			}
			return TypeCombinator::union(
				$type->toFloat(),
				$type->toInteger()
			);
		});
	}

	/**
	 * Returns whether the query has aggregate function and no group by clause
	 *
	 * Queries with aggregate functions and no group by clause always have
	 * exactly 1 group. This implies that they return exactly 1 row, and that
	 * all column can have a null value.
	 *
	 * c.f. SQL92, section 7.9, General Rules
	 */
	private function hasAggregateWithoutGroupBy(): bool
	{
		return $this->hasAggregateFunction && !$this->hasGroupByClause;
	}

	private function hasAggregateFunction(AST\SelectStatement $AST): bool
	{
		foreach ($AST->selectClause->selectExpressions as $selectExpression) {
			if (!$selectExpression instanceof AST\SelectExpression) {
				continue;
			}

			$expression = $selectExpression->expression;

			switch (true) {
				case $expression instanceof AST\Functions\AvgFunction:
				case $expression instanceof AST\Functions\CountFunction:
				case $expression instanceof AST\Functions\MaxFunction:
				case $expression instanceof AST\Functions\MinFunction:
				case $expression instanceof AST\Functions\SumFunction:
				case $expression instanceof AST\AggregateExpression:
					return true;
				default:
					break;
			}
		}

		return false;
	}

}
