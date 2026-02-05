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

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Zenstruck\Assert;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryAssertions;

use function Zenstruck\Foundry\get;
use function Zenstruck\Foundry\Persistence\refresh;

/**
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 *
 * @phpstan-import-type Parameters from Factory
 */
abstract class AbstractFoundryContext implements Context
{
    public function __construct(
        private readonly FactoryShortNameResolver $factoryResolver,
        private readonly ObjectRegistry $objectRegistry,
    ) {
    }

    public function createObject(string $factoryShortName, ?string $objectName = null): void
    {
        $this->resolveFactory($factoryShortName, $objectName)->create();
    }

    public function createObjectWithProperties(TableNode $table, string $factoryShortName, ?string $objectName = null): void
    {
        $factory = $this->resolveFactory($factoryShortName, $objectName);
        $parametersList = $table->getColumnsHash();

        if (1 !== \count($parametersList)) {
            throw new \InvalidArgumentException('Expected exactly one line of properties, to create one object.');
        }

        $factory->create($parametersList[0]);
    }

    public function createObjectsWithProperties(TableNode $table, string $factoryShortName): void
    {
        $parametersList = $table->getColumnsHash();

        foreach ($parametersList as $parameters) {
            $objectName = $parameters['_ref'] ?? null;
            unset($parameters['_ref']);

            $this->resolveFactory($factoryShortName, $objectName)
                ->create($parameters);
        }
    }

    public function assertNbObjectsExist(int $nb, string $factoryShortName): void
    {
        $this->repositoryAssertionFor($factoryShortName)
            ->count($nb);
    }

    public function assertObjectHasProperties(FoundryTableNode $table, string $factoryShortName, string $objectName): void
    {
        $parametersList = $table->getColumnsHash();

        if (1 !== \count($parametersList)) {
            throw new \InvalidArgumentException('Expected exactly one line of properties.');
        }

        $object = $this->objectRegistry->getByFactoryShortName($factoryShortName, $objectName);

        if (!Configuration::autoRefreshWithLazyObjectsIsEnabled()) {
            refresh($object);
        }

        foreach ($parametersList[0] as $key => $valueExpected) {
            $actualValue = get($object, $key);

            match (true) {
                $valueExpected instanceof \DateTimeInterface => Assert::that($actualValue)
                    ->isInstanceOf(\DateTimeInterface::class)
                    ->and($actualValue->format('Y-m-d H:i:s'))
                    ->is($valueExpected->format('Y-m-d H:i:s')),

                \is_object($valueExpected) => Assert::that($actualValue)->is($valueExpected),

                default => Assert::that($actualValue)->equals($valueExpected),
            };
        }
    }

    public function assertObjectExists(string $factoryShortName, string $objectName): void
    {
        Assert::that(
            $this->objectRegistry->has(
                $this->factoryResolver->targetObjectClassFor($factoryShortName),
                $objectName
            )
        )->is(true, "Object with name \"{$objectName}\" of type \"{$factoryShortName}\" does not exist although it should.");
    }

    public function assertObjectDoesNotExist(string $factoryShortName, string $objectName): void
    {
        Assert::that(
            $this->objectRegistry->has(
                $this->factoryResolver->targetObjectClassFor($factoryShortName),
                $objectName
            )
        )->is(false, "Object with name \"{$objectName}\" of type \"{$factoryShortName}\" exists although it should not.");
    }

    public function transformLastId(string $before, string $after): string
    {
        return "{$before}{$this->objectRegistry->lastId()}{$after}";
    }

    public function transformLastIdForSpecificObject(string $before, string $factoryShortName, string $after): string
    {
        return "{$before}{$this->objectRegistry->lastIdFor($factoryShortName)}{$after}";
    }

    public function transformIdForSpecificObject(string $before, string $factoryShortName, string $objectName, string $after): string
    {
        return "{$before}{$this->objectRegistry->idFor($factoryShortName, $objectName)}{$after}";
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

    private function repositoryAssertionFor(string $factoryShortName): RepositoryAssertions
    {
        $factory = $this->factoryResolver->factoryFor($factoryShortName);

        if (!$factory instanceof PersistentObjectFactory) {
            throw new \LogicException(\sprintf('Cannot make assertions with factory of class "%s" with short name "%s": it does not extend "%s".', $factory::class, $factoryShortName, PersistentObjectFactory::class));
        }

        return $factory::assert();
    }
}
