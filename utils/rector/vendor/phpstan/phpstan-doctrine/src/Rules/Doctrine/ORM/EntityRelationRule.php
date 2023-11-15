<?php declare(strict_types = 1);

namespace PHPStan\Rules\Doctrine\ORM;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Doctrine\ObjectMetadataResolver;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;
use PHPStan\Type\VerbosityLevel;
use Throwable;
use function get_class;
use function in_array;
use function sprintf;

/**
 * @implements Rule<ClassPropertyNode>
 */
class EntityRelationRule implements Rule
{

	/** @var ObjectMetadataResolver */
	private $objectMetadataResolver;

	/** @var bool */
	private $allowNullablePropertyForRequiredField;

	/** @var bool */
	private $bleedingEdge;

	public function __construct(
		ObjectMetadataResolver $objectMetadataResolver,
		bool $allowNullablePropertyForRequiredField,
		bool $bleedingEdge
	)
	{
		$this->objectMetadataResolver = $objectMetadataResolver;
		$this->allowNullablePropertyForRequiredField = $allowNullablePropertyForRequiredField;
		$this->bleedingEdge = $bleedingEdge;
	}

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->bleedingEdge && !$this->objectMetadataResolver->hasObjectManagerLoader()) {
			return [];
		}

		$class = $scope->getClassReflection();
		if ($class === null) {
			return [];
		}

		$className = $class->getName();
		$metadata = $this->objectMetadataResolver->getClassMetadata($className);
		if ($metadata === null) {
			return [];
		}

		$propertyName = $node->getName();
		if (!isset($metadata->associationMappings[$propertyName])) {
			return [];
		}
		$associationMapping = $metadata->associationMappings[$propertyName];
		$identifiers = [];
		try {
			$identifiers = $metadata->getIdentifierFieldNames();
		} catch (Throwable $e) {
			$mappingException = 'Doctrine\ORM\Mapping\MappingException';
			if (!$e instanceof $mappingException) {
				throw $e;
			}
		}

		$columnType = null;
		$toMany = false;
		if ((bool) ($associationMapping['type'] & 3)) { // ClassMetadataInfo::TO_ONE
			$columnType = new ObjectType($associationMapping['targetEntity']);
			if (in_array($propertyName, $identifiers, true)) {
				$nullable = false;
			} else {
				/** @var bool $nullable */
				$nullable = $associationMapping['joinColumns'][0]['nullable'] ?? true;
			}
			if ($nullable) {
				$columnType = TypeCombinator::addNull($columnType);
			}
		} elseif ((bool) ($associationMapping['type'] & 12)) { // ClassMetadataInfo::TO_MANY
			$toMany = true;
			$columnType = TypeCombinator::intersect(
				new ObjectType('Doctrine\Common\Collections\Collection'),
				new IterableType(new MixedType(), new ObjectType($associationMapping['targetEntity']))
			);
		}

		$phpDocType = $node->getPhpDocType();
		$nativeType = $node->getNativeType() !== null ? ParserNodeTypeToPHPStanType::resolve($node->getNativeType(), $scope->getClassReflection()) : new MixedType();
		$propertyType = TypehintHelper::decideType($nativeType, $phpDocType);

		$errors = [];
		if ($columnType !== null) {
			if (get_class($propertyType) === MixedType::class || $propertyType instanceof ErrorType || $propertyType instanceof NeverType) {
				return [];
			}

			$collectionObjectType = new ObjectType('Doctrine\Common\Collections\Collection');
			$propertyTypeToCheckAgainst = $propertyType;
			if (
				$toMany
				&& $collectionObjectType->isSuperTypeOf($propertyType)->yes()
				&& $propertyType->isIterable()->yes()
			) {
				$propertyTypeToCheckAgainst = TypeCombinator::intersect(
					$collectionObjectType,
					new IterableType(new MixedType(true), $propertyType->getIterableValueType())
				);
			}
			if (!$propertyTypeToCheckAgainst->isSuperTypeOf($columnType)->yes()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Property %s::$%s type mapping mismatch: database can contain %s but property expects %s.',
					$className,
					$propertyName,
					$columnType->describe(VerbosityLevel::typeOnly()),
					$propertyType->describe(VerbosityLevel::typeOnly())
				))->identifier('doctrine.associationType')->build();
			}
			if (
				!$columnType->isSuperTypeOf(
					$this->allowNullablePropertyForRequiredField
						? TypeCombinator::removeNull($propertyType)
						: $propertyType
				)->yes()
			) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Property %s::$%s type mapping mismatch: property can contain %s but database expects %s.',
					$className,
					$propertyName,
					$propertyType->describe(VerbosityLevel::typeOnly()),
					$columnType->describe(VerbosityLevel::typeOnly())
				))->identifier('doctrine.associationType')->build();
			}
		}

		return $errors;
	}

}
