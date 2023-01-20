<?php

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Behat\Context\Context;
use Psr\Container\ContainerInterface;
use Zenstruck\Foundry\ChainManagerRegistry;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Test\LazyManagerRegistry;
use Zenstruck\Foundry\Test\TestState;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FactoriesContext implements Context
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @BeforeScenario
     */
    public function setUpFactories(): void
    {
        TestState::bootFromContainer($this->container);
        Factory::configuration()->setManagerRegistry(
            new LazyManagerRegistry(function (): ChainManagerRegistry {
                return TestState::initializeChainManagerRegistry($this->container);
            })
        );
    }

    /**
     * @AfterScenario
     */
    public function tearDownFactories(): void
    {
        TestState::shutdownFoundry();
    }
}
