<?php

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Behat\Definition\Call\DefinitionCall;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Call\Call;
use Behat\Testwork\Call\Filter\CallFilter;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Test\Behat\Exception\InvalidObjectParameter;
use Zenstruck\Foundry\Test\Behat\Exception\ObjectNotFound;

/**
 * @internal
 *
 * Transforms TableNodes into FoundryTableNodes where all types are resolved.
 */
final class FoundryCallFilter implements CallFilter
{
    private readonly FactoryShortNameResolver $factoryResolver;
    private readonly ObjectRegistry $objectRegistry;

    public function __construct(
        KernelInterface $symfonyKernel,
    ) {
        $this->factoryResolver = $symfonyKernel->getContainer()->get('.zenstruck_foundry.behat.factory_resolver'); // @phpstan-ignore assign.propertyType
        $this->objectRegistry = $symfonyKernel->getContainer()->get('.zenstruck_foundry.behat.object_registry'); // @phpstan-ignore assign.propertyType
    }

    public function supportsCall(Call $call): bool
    {
        return array_any(
            $call->getArguments(),
            static fn($argument) => $argument instanceof TableNode && !$argument instanceof ExampleTableNode
        );
    }

    public function filterCall(Call $call): Call
    {
        if (
            !$call instanceof DefinitionCall
            || !$call->getCallee()->getReflection() instanceof \ReflectionMethod
            || $call->getCallee()->getReflection()->class !== FoundryContext::class
        ) {
            return $call;
        }

        $arguments = $call->getArguments();

        if (!isset($arguments['factoryShortName'])) {
            throw new \InvalidArgumentException(
                <<<ERROR
                Cannot filter call without a "\$factoryShortName" argument. 
                This must be the name of the argument in the #[Given], #[When], #[Then] definitions."
                ERROR
            );
        }
        
        return new DefinitionCall(
            $call->getEnvironment(),
            $call->getFeature(),
            $call->getStep(),
            $call->getCallee(),
            array_map(
                fn(mixed $argument) => match ($argument instanceof TableNode) {
                    true => $this->normalizeObjectParameters($argument, $arguments['factoryShortName']),
                    false => $argument,
                },
                $arguments
            ),
            $call->getErrorReportingLevel(),
        );
    }

    private function normalizeObjectParameters(TableNode $tableNode, string $factoryShortName): TableNode
    {
        $table = $tableNode->getTable();

        $headKey = array_key_first($table);
        $thead = array_shift($table);

        return FoundryTableNode::create(
            $this->factoryResolver,
            $this->objectRegistry,
            (\Closure::bind(
                fn () => $this->maxLineLength,
                $tableNode,
                TableNode::class
            )()),
            [ // @phpstan-ignore argument.type (TableNode has the same problem: array $table is not really lists)
                $headKey => $thead, // @phpstan-ignore array.invalidKey
                ...array_map(
                    function (array $parameters) use ($thead, $factoryShortName): array {
                        $normalized = [];
                        foreach ($parameters as $key => $value) {
                            if (!isset($thead[$key])) {
                                throw new \LogicException("Table has no column for parameter \"$key\". This should never happen, table integrity is checked in TableNode.");
                            }

                            $propertyName = $thead[$key];

                            if ($propertyName === '_ref') {
                                $normalized['_ref'] = $value;

                                continue;
                            }

                            if ('null' === $value) {
                                $normalized[$propertyName] = null;

                                continue;
                            }

                            if ('true' === $value) {
                                $normalized[$propertyName] = true;

                                continue;
                            }

                            if ('false' === $value) {
                                $normalized[$propertyName] = false;

                                continue;
                            }

                            if (preg_match('/^<ref\((?<factoryShortName>[^,]+), (?<objectName>[^)]+)\)>$/', $value, $matches)) {
                                try {
                                    $normalized[$propertyName] = $this->objectRegistry->getByFactoryShortName($matches['factoryShortName'], $matches['objectName']);
                                } catch (ObjectNotFound $e) {
                                    throw InvalidObjectParameter::objectReferencedInTableDoesNotExist($propertyName, $e);
                                }

                                continue;
                            }

                            $targetClass = $this->factoryResolver->targetObjectClassFor($factoryShortName);
                            $expectedTypeClass = $this->getPropertyTypeIfClass(new \ReflectionClass($targetClass), $propertyName);

                            if (!$expectedTypeClass) {
                                $normalized[$propertyName] = $value;

                                continue;
                            }

                            if ($this->factoryResolver->hasFactoryForClass($expectedTypeClass)) {
                                try {
                                    $normalized[$propertyName] = $this->objectRegistry->getByObjectClass($expectedTypeClass, $value);
                                } catch (ObjectNotFound $e) {
                                    throw InvalidObjectParameter::objectReferencedInTableDoesNotExist($propertyName, $e);
                                }

                                continue;
                            }

                            if (is_a($expectedTypeClass, \DateTimeInterface::class, allow_string: true)) {
                                try {
                                    $normalized[$propertyName] = new $expectedTypeClass($value);

                                    continue;
                                } catch (\Throwable $e) { // @phpstan-ignore catch.neverThrown
                                    throw InvalidObjectParameter::invalidDate($propertyName, $value, $e);
                                }
                            }

                            if (is_a($expectedTypeClass, \BackedEnum::class, allow_string: true)) {
                                $value = is_numeric($value) ? (int)$value : $value;

                                $normalized[$propertyName] = $expectedTypeClass::tryFrom($value) ?? throw InvalidObjectParameter::invalidEnumValue($propertyName, (string)$value);

                                continue;
                            }

                            throw new \LogicException("Cannot normalize parameter \"$propertyName\" with value \"$value\".");
                        }

                        return $normalized;
                    },
                    $table
                ),
            ]
        );
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
            || !class_exists($type->getName())
        ) {
            return null;
        }

        return $type->getName();
    }
}
