<?php

namespace Zenstruck\Foundry\Tests\Fixture\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Hook\BeforeScenario;
use Symfony\Component\HttpKernel\KernelInterface;
use function Zenstruck\Foundry\application;
use function Zenstruck\Foundry\runCommand;

final class TestContext implements Context
{
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    #[BeforeScenario]
    public function createDB(): void
    {
        $application = application($this->kernel);

        runCommand($application, 'doctrine:schema:drop --force');
        runCommand($application, 'doctrine:schema:create');
    }
}
