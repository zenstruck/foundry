<?php

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class FoundryContext implements Context
{
    public function __construct(
        private readonly FactoryResolver $factoryResolver
    ) {
    }

    #[Given('a :factoryShortName is created')]
    #[Given('a :factoryShortName :objectName is created')]
    public function createObject(string $factoryShortName, ?string $objectName = null): void
    {
        $this->factoryResolver->resolve($factoryShortName)->create();
    }

    #[Then(':nb :factoryShortName should exist')]
    public function assertNbObjectsExist(int $nb, string $factoryShortName): void
    {
        $factory = $this->factoryResolver->resolve($factoryShortName);

        if (!$factory instanceof PersistentObjectFactory) {
            throw new \LogicException(
                sprintf(
                    "Cannot make assertions with factory of class \"%s\" with short name '$factoryShortName': it does not extend \"%s\".",
                    $factory::class,
                    PersistentObjectFactory::class
                )
            );
        }

        $factory::assert()->count($nb);
    }
}
