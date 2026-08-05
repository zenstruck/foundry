<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Gherkin\Node\TableNode;
use Zenstruck\Foundry\Test\Behat\Exception\CompositeIdentifierNotSupported;
use Zenstruck\Foundry\Test\Behat\Exception\InvalidObjectParameter;
use Zenstruck\Foundry\Test\Behat\Exception\ObjectNotFound;

/**
 * Normalizes the property tables of the built-in steps into factory attributes:
 * resolves null/true/false literals, explicit object references
 * (<foundry:object(...)>, <foundry:lastObject(...)>) and, based on the type of the
 * target class' property, registered objects, dates and enums.
 *
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class TableParametersNormalizer
{
    public function __construct(
        private readonly FactoryShortNameResolver $factoryResolver,
        private readonly ObjectRegistry $objectRegistry,
    ) {
    }

    /**
     * @return list<array<string, mixed>> one entry per body row, keyed by property name ("_ref" kept as-is)
     */
    public function normalize(TableNode $table, string $factoryShortName): array
    {
        return \array_map(
            fn(array $row) => $this->normalizeRow($row, $factoryShortName),
            $table->getColumnsHash(),
        );
    }

    /**
     * @param array<string, string> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row, string $factoryShortName): array
    {
        $normalized = [];
        foreach ($row as $propertyName => $value) {
            if ('_ref' === $propertyName) {
                $normalized['_ref'] = $value;

                continue;
            }

            $normalized[$propertyName] = match (true) {
                'null' === $value => null,
                'true' === $value => true,
                'false' === $value => false,
                default => $this->resolveExplicitObjectReference($propertyName, $value)
                    ?? $this->resolveObjectReferenceBasedOnPropertyType($propertyName, $value, $factoryShortName),
            };
        }

        return $normalized;
    }

    private function resolveExplicitObjectReference(string $propertyName, string $value): ?object
    {
        if (\preg_match('/^<foundry:lastObject\(\s*(?<factoryShortName>[^)]+?)\s*\)>$/', $value, $matches)) {
            try {
                return $this->objectRegistry->lastObjectFor(self::unquote($matches['factoryShortName']));
            } catch (CompositeIdentifierNotSupported $e) {
                throw InvalidObjectParameter::compositeIdentifier($propertyName, $e);
            }
        }

        if (!\preg_match('/^<foundry:object\(\s*(?<factoryShortName>[^,)]+?)\s*,\s*(?<objectName>[^)]+?)\s*\)>$/', $value, $matches)) {
            return null;
        }

        try {
            return $this->objectRegistry->getByFactoryShortName(self::unquote($matches['factoryShortName']), self::unquote($matches['objectName']));
        } catch (ObjectNotFound $e) {
            throw InvalidObjectParameter::objectReferencedInTableDoesNotExist($propertyName, $e);
        }
    }

    /**
     * Same quoting rules as the <foundry:id()>/<foundry:lastId()> placeholders.
     */
    private static function unquote(string $value): string
    {
        return \trim($value, '"');
    }

    private function resolveObjectReferenceBasedOnPropertyType(string $propertyName, string $value, string $factoryShortName): mixed
    {
        $targetClass = $this->factoryResolver->targetObjectClassFor($factoryShortName);
        $expectedTypeClass = $this->getPropertyTypeIfClass(new \ReflectionClass($targetClass), $propertyName);

        if (!$expectedTypeClass) {
            return $value;
        }

        if ($this->factoryResolver->hasFactoryForClass($expectedTypeClass)) {
            try {
                return $this->objectRegistry->getByObjectClass($expectedTypeClass, $value);
            } catch (ObjectNotFound $e) {
                throw InvalidObjectParameter::objectReferencedInTableDoesNotExist($propertyName, $e);
            }
        }

        if (\is_a($expectedTypeClass, \DateTimeInterface::class, allow_string: true)) {
            try {
                return new $expectedTypeClass($value);
            } catch (\Throwable $e) { // @phpstan-ignore catch.neverThrown
                throw InvalidObjectParameter::invalidDate($propertyName, $value, $e);
            }
        }

        if (\is_a($expectedTypeClass, \BackedEnum::class, allow_string: true)) {
            $value = \is_numeric($value) ? (int) $value : $value;

            return $expectedTypeClass::tryFrom($value) ?? throw InvalidObjectParameter::invalidEnumValue($propertyName, (string) $value);
        }

        throw new \LogicException("Cannot normalize parameter \"{$propertyName}\" with value \"{$value}\".");
    }

    /**
     * @param \ReflectionClass<object> $class
     *
     * @return class-string|null
     */
    private function getPropertyTypeIfClass(\ReflectionClass $class, string $propertyName): ?string
    {
        try {
            $property = $class->getProperty($propertyName);
        } catch (\ReflectionException) {
            if ($class = $class->getParentClass()) {
                return $this->getPropertyTypeIfClass($class, $propertyName);
            }
        }

        if (
            !isset($property)
            || !($type = $property->getType()) instanceof \ReflectionNamedType
            || $type->isBuiltin()
            || !\class_exists($type->getName())
        ) {
            return null;
        }

        return $type->getName();
    }
}
