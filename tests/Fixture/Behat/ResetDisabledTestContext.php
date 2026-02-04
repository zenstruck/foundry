<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
