<?php

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
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
        $parametersList = $this->normalizeObjectParameters($table->getColumnsHash());

        if (count($parametersList) !== 1) {
            throw new \InvalidArgumentException('Expected exactly one line of properties, to create one object.');
        }

        $this->resolveFactory($factoryShortName, $objectName)
            ->create($parametersList[0]);
    }

    /**
     * @phpstan-param list<Parameters> $parametersList
     * @phpstan-return list<Parameters>
     */
    private function normalizeObjectParameters(array $parametersList): array
    {
        return array_map(
            fn(array $parameters) => array_map(
                function (mixed $value) {
                    if (preg_match('/^<ref\((?<factoryShortName>[^,]+), (?<objectName>[^)]+)\)>$/', $value, $matches)) {
                        return $this->objectRegistry->get($matches['factoryShortName'], $matches['objectName']);
                    }

                    return $value;
                },
                $parameters
            ),
            $parametersList
        );
    }

    #[Given(':factoryShortName are created with properties')]
    public function createObjectsWithProperties(TableNode $table, string $factoryShortName): void
    {
        $attributes = $table->getColumnsHash();

        foreach ($attributes as $attribute) {
            $objectName = $attribute['_ref'] ?? null;
            unset($attribute['_ref']);

            $this->resolveFactory($factoryShortName, $objectName)
                ->create($attribute);
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
        $parametersList = $this->normalizeObjectParameters($table->getColumnsHash());

        if (count($parametersList) !== 1) {
            throw new \InvalidArgumentException('Expected exactly one line of properties.');
        }

        $object = $this->objectRegistry->get($factoryShortName, $objectName);

        if (!Configuration::autoRefreshWithLazyObjectsIsEnabled()) {
            refresh($object);
        }

        foreach ($parametersList[0] as $key => $value) {
            Assert::that(get($object, $key))->is($value);
        }
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
}
