<?php

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Zenstruck\Assert;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Persistence\RepositoryAssertions;
use function Zenstruck\Foundry\get;
use function Zenstruck\Foundry\Persistence\refresh;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class FoundryContext implements Context
{
    public function __construct(
        private readonly FactoryShortNameResolver $factoryResolver,
        private readonly ObjectRegistry $objectRegistry,
    ) {
    }

    #[Given('a :factoryShortName is created')]
    #[Given('a :factoryShortName :objectName is created')]
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
            fn(object $object) => $this->objectRegistry->store($factoryShortName, $objectName, $object)
        );
    }

    #[Given('a :factoryShortName is created with properties')]
    #[Given('a :factoryShortName :objectName is created with properties')]
    public function createObjectWithProperties(TableNode $table, string $factoryShortName, ?string $objectName = null): void
    {
        $attributes = $table->getColumnsHash();

        if (count($attributes) !== 1) {
            throw new \InvalidArgumentException('Expected exactly one line of properties.');
        }

        $this->resolveFactory($factoryShortName, $objectName)
            ->create(
                $attributes[0]
            );
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
        $attributes = $table->getColumnsHash();

        if (count($attributes) !== 1) {
            throw new \InvalidArgumentException('Expected exactly one line of properties.');
        }

        $object = $this->objectRegistry->get($factoryShortName, $objectName);
        refresh($object);

        foreach ($attributes[0] as $key => $value) {
            Assert::that(get($object, $key))->is($value);
        }
    }
}
