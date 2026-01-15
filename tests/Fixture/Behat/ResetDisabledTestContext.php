<?php

namespace Zenstruck\Foundry\Tests\Fixture\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;

/**
 * Context for testing disabled mode - resets DB only before the first feature.
 */
final class ResetDisabledTestContext implements Context
{
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    #[BeforeScenario]
    public function resetDBOnce(): void
    {
        ResetDatabaseManager::resetBeforeFirstTest($this->kernel);
    }
}
