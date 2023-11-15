<?php declare(strict_types = 1);

namespace PHPStan\Type\Doctrine\Query;

use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VoidType;
use function array_key_last;
use function count;
use function is_int;

/**
 * QueryResultTypeBuilder helps building the result type of a query
 *
 * Like Doctrine\ORM\Query\ResultSetMapping, but for static typing concerns
 */
final class QueryResultTypeBuilder
{

	/** @var bool */
	private $selectQuery = false;

	/**
	 * Whether the result is an array shape or a single entity or NEW object
	 *
	 * @var bool
	 */
	private $isShape = false;

	/**
	 * Map from selected entity aliases to entity types
	 *
	 * Example: "e" is an entity alias in "SELECT e FROM Entity e"
	 *
	 * @var array<array-key,Type>
	 */
	private $entities = [];

	/**
	 * Map from selected entity alias to result alias
	 *
	 * Example: "hello" is a result alias in "SELECT e AS hello FROM Entity e"
	 *
	 * @var array<array-key,string>
	 */
	private $entityResultAliases = [];

	/**
	 * Map from selected scalar result alias to scalar type
	 *
	 * @var array<array-key,Type>
	 */
	private $scalars = [];

	/**
	 * Map from selected NEW objcet result alias to NEW object type
	 *
	 * @var array<array-key,Type>
	 */
	private $newObjects = [];

	/** @var Type */
	private $indexedBy;

	public function __construct()
	{
		$this->indexedBy = new NullType();
	}

	public function setSelectQuery(): void
	{
		$this->selectQuery = true;
	}

	public function isSelectQuery(): bool
	{
		return $this->selectQuery;
	}

	public function addEntity(string $entityAlias, Type $type, ?string $resultAlias): void
	{
		$this->entities[$entityAlias] = $type;
		if ($resultAlias === null) {
			return;
		}

		$this->entityResultAliases[$entityAlias] = $resultAlias;
		$this->isShape = true;
	}

	/**
	 * @return array<array-key,Type>
	 */
	public function getEntities(): array
	{
		return $this->entities;
	}

	/**
	 * @param array-key $alias
	 */
	public function addScalar($alias, Type $type): void
	{
		$this->scalars[$alias] = $type;
		$this->isShape = true;
	}

	/**
	 * @return array<int,Type>
	 */
	public function getScalars(): array
	{
		return $this->scalars;
	}

	/**
	 * @param array-key $alias
	 */
	public function addNewObject($alias, Type $type): void
	{
		$this->newObjects[$alias] = $type;
		if (count($this->newObjects) <= 1) {
			return;
		}

		$this->isShape = true;
	}

	/**
	 * @return array<int,Type>
	 */
	public function getNewObjects(): array
	{
		return $this->newObjects;
	}

	public function getResultType(): Type
	{
		// There are a few special cases here, depending on what is selected:
		//
		// - Just one entity:
		//   - Without alias: Result is the entity
		//   - With an alias: array{alias: entity}
		//
		// - One NEW object, with any entities:
		//   - Result is the NEW object (entities are ignored, alias is ignored)
		//
		// - NEW objects and/or scalars:
		//   - Result is an array shape with one element per NEW object and
		//     scalar. Keys are the aliases or an incremental numeric index
		//     (scalars start at 1, NEW objects are 0). NEW objects can shadow
		//     scalars.
		//
		// - Multiple arbitrarily joint entities:
		//   - Without aliases: Result is an alternation of the entities,
		//     like Entity1|Entity2|EntityN
		//   - With aliases: Result is an alternation of array shapes,
		//     like array{alias: Entity1}|array{alias: Entity2}|...
		//
		// - One or more entities plus scalars and NEW objects:
		//   - Result is an alternation of intersections of the shapes described
		//     in  "Multiple arbitrarily joint entities" and "NEW objects and/or
		//     scalars". Entities without a alias are at offset 0 in the shape.

		// We use Void for non-select queries. This is used as a marker by the
		// DynamicReturnTypeExtension for Query::getResult() and variants.
		if (!$this->selectQuery) {
			return new VoidType();
		}

		// If there is a single NEW object and no scalars, the result is the
		// NEW object. This ignores any entity.
		// https://github.com/doctrine/orm/blob/v2.7.3/lib/Doctrine/ORM/Internal/Hydration/ObjectHydrator.php#L566-L570
		if (count($this->newObjects) === 1 && count($this->scalars) === 0) {
			foreach ($this->newObjects as $newObjects) {
				return $newObjects;
			}
		}

		if (count($this->entities) === 0) {
			$builder = ConstantArrayTypeBuilder::createEmpty();
			$this->addNonEntitiesToShapeResult($builder);
			return $builder->getArray();
		}

		$alternatives = [];
		$lastEntityAlias = array_key_last($this->entities);

		foreach ($this->entities as $entityAlias => $entityType) {
			if (!$this->isShape) {
				$alternatives[] = $entityType;

				continue;
			}

			$resultAlias = $this->entityResultAliases[$entityAlias] ?? 0;
			$offsetType = $this->resolveOffsetType($resultAlias);

			$builder = ConstantArrayTypeBuilder::createEmpty();

			$builder->setOffsetValueType($offsetType, $entityType);

			if ($entityAlias === $lastEntityAlias) {
				$this->addNonEntitiesToShapeResult($builder);
			}

			$alternatives[] = $builder->getArray();
		}

		return TypeCombinator::union(...$alternatives);
	}

	private function addNonEntitiesToShapeResult(ConstantArrayTypeBuilder $builder): void
	{
		foreach ($this->scalars as $alias => $scalarType) {
			$offsetType = $this->resolveOffsetType($alias);
			$builder->setOffsetValueType($offsetType, $scalarType);
		}

		foreach ($this->newObjects as $alias => $newObjectType) {
			$offsetType = $this->resolveOffsetType($alias);
			$builder->setOffsetValueType($offsetType, $newObjectType);
		}
	}

	/**
	 * @param array-key $alias
	 */
	private function resolveOffsetType($alias): Type
	{
		if (is_int($alias)) {
			return new ConstantIntegerType($alias);
		}

		return new ConstantStringType($alias);
	}


	public function setIndexedBy(Type $type): void
	{
		$this->indexedBy = $type;
	}

	public function getIndexType(): Type
	{
		if (!$this->selectQuery) {
			return new VoidType();
		}

		return $this->indexedBy;
	}

}
