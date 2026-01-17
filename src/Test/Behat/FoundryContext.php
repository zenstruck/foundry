<?php

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Hook\BeforeStep;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Transformation\Transform;
use Zenstruck\Assert;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryAssertions;
use function Zenstruck\Foundry\get;
use function Zenstruck\Foundry\Persistence\refresh;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @phpstan-import-type Parameters from Factory
 */
final class FoundryContext implements Context
{
    public function __construct(
        private readonly FactoryShortNameResolver $factoryResolver,
        private readonly ObjectRegistry $objectRegistry,
    ) {
    }

    #[Given('a(n) :factoryShortName is created')]
    #[Given('a(n) :factoryShortName :objectName is created')]
    public function createObject(string $factoryShortName, ?string $objectName = null): void
    {
        $this->resolveFactory($factoryShortName, $objectName)->create();
    }

    /**
     * @return ObjectFactory<object>
     */
    private function resolveFactory(string $factoryShortName, ?string $objectName = null): ObjectFactory
    {
        $factory = $this->factoryResolver->factoryFor($factoryShortName);

        if (!$objectName) {
            return $factory;
        }

        return $factory->afterInstantiate(
            fn(object $object) => $this->objectRegistry->store($object, $objectName)
        );
    }

    #[Given('a(n) :factoryShortName is created with properties')]
    #[Given('a(n) :factoryShortName :objectName is created with properties')]
    public function createObjectWithProperties(TableNode $table, string $factoryShortName, ?string $objectName = null): void
    {
        $factory = $this->resolveFactory($factoryShortName, $objectName);
        $parametersList = $this->normalizeObjectParameters($table, $factory::class());

        if (count($parametersList) !== 1) {
            throw new \InvalidArgumentException('Expected exactly one line of properties, to create one object.');
        }

        $factory->create($parametersList[0]);
    }

    /**
     * @param class-string $targetClass
     * @phpstan-return list<Parameters>
     */
    private function normalizeObjectParameters(TableNode $table, string $targetClass): array
    {
        return array_map(
            function (array $parameters) use ($targetClass): array {
                $normalized = [];
                foreach ($parameters as $propertyName => $value) {
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
                        $normalized[$propertyName] = $this->objectRegistry->getByFactoryShortName($matches['factoryShortName'], $matches['objectName']);

                        continue;
                    }

                    $expectedTypeClass = $this->getPropertyTypeIfClass(new \ReflectionClass($targetClass), $propertyName);

                    if (!$expectedTypeClass) {
                        $normalized[$propertyName] = $value;

                        continue;
                    }

                    if ($this->factoryResolver->hasFactoryForClass($expectedTypeClass)) {
                        try {
                            $normalized[$propertyName] = $this->objectRegistry->getByObjectClass($expectedTypeClass, $value);
                        } catch (ObjectNotFoundException $e) {
                            throw InvalidObjectParameter::objectReferencedInTableDoesNotExist($propertyName, $e);
                        }

                        continue;
                    }

                    if (is_a($expectedTypeClass, \DateTimeInterface::class, true)) {
                        try {
                            $normalized[$propertyName] = new $expectedTypeClass($value);

                            continue;
                        } catch (\Throwable $e) {
                            throw InvalidObjectParameter::invalidDate($propertyName, $value, $e);
                        }
                    }
                }

                return $normalized;
            },
            $table->getColumnsHash()
        );
    }

    #[Given(':factoryShortName are created with properties')]
    public function createObjectsWithProperties(TableNode $table, string $factoryShortName): void
    {
        $targetClass = $this->factoryResolver->targetObjectClassFor($factoryShortName);
        $parametersList = $this->normalizeObjectParameters($table, $targetClass);

        foreach ($parametersList as $parameters) {
            $objectName = $parameters['_ref'] ?? null;
            unset($parameters['_ref']);

            $this->resolveFactory($factoryShortName, $objectName)
                ->create($parameters);
        }
    }

    #[Then(':nb :factoryShortName should exist')]
    public function assertNbObjectsExist(int $nb, string $factoryShortName): void
    {
        $this->repositoryAssertionFor($factoryShortName)
            ->count($nb);
    }

    private function repositoryAssertionFor(string $factoryShortName): RepositoryAssertions
    {
        $factory = $this->factoryResolver->factoryFor($factoryShortName);

        if (!$factory instanceof PersistentObjectFactory) {
            throw new \LogicException(
                \sprintf(
                    "Cannot make assertions with factory of class \"%s\" with short name \"%s\": it does not extend \"%s\".",
                    $factory::class,
                    $factoryShortName,
                    PersistentObjectFactory::class
                )
            );
        }

        return $factory::assert();
    }

    #[Then(':factoryShortName :objectName should have properties')]
    public function assertObjectHasProperties(TableNode $table, string $factoryShortName, string $objectName): void
    {
        $parametersList = $this->normalizeObjectParameters(
            $table,
            $this->factoryResolver->targetObjectClassFor($factoryShortName)
        );

        if (count($parametersList) !== 1) {
            throw new \InvalidArgumentException('Expected exactly one line of properties.');
        }

        $object = $this->objectRegistry->getByFactoryShortName($factoryShortName, $objectName);

        if (!Configuration::autoRefreshWithLazyObjectsIsEnabled()) {
            refresh($object);
        }

        foreach ($parametersList[0] as $key => $valueExpected) {
            $actualValue = get($object, $key);

            match(true) {
                $valueExpected instanceof \DateTimeInterface => Assert::that($actualValue)
                    ->isInstanceOf(\DateTimeInterface::class)
                    ->and($actualValue->format('Y-m-d H:i:s'))
                    ->is($valueExpected->format('Y-m-d H:i:s')),

                is_object($valueExpected) => Assert::that($actualValue)->is($valueExpected),

                default => Assert::that($actualValue)->equals($valueExpected)
            };
        }
    }

    #[Then(':factoryShortName object named :objectName should exist')]
    public function assertObjectExists(string $factoryShortName, string $objectName): void
    {
        Assert::that(
            $this->objectRegistry->has(
                $this->factoryResolver->targetObjectClassFor($factoryShortName),
                $objectName
            )
        )->is(true, "Object with name \"$objectName\" of type \"$factoryShortName\" does not exist although it should.");
    }

    #[Then(':factoryShortName object named :objectName should not exist')]
    public function assertObjectDoesNotExist(string $factoryShortName, string $objectName): void
    {
        Assert::that(
            $this->objectRegistry->has(
                $this->factoryResolver->targetObjectClassFor($factoryShortName),
                $objectName
            )
        )->is(false, "Object with name \"$objectName\" of type \"$factoryShortName\" exists although it should not.");
    }

    #[Transform('/(.*)<lastId>(.*)/')]
    public function transformLastId(string $before, string $after): string
    {
        return "{$before}{$this->objectRegistry->lastId()}{$after}";
    }

    #[Transform('/(.*)<lastId\((.*)\)>(.*)/')]
    public function transformLastIdForSpecificObject(string $before, string $factoryShortName, string $after): string
    {
        return "{$before}{$this->objectRegistry->lastIdFor($factoryShortName)}{$after}";
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
