<?php

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Behat\Context\Context;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Test\DatabaseResetter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ResetDatabaseContext implements Context
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    /**
     * @BeforeScenario
     */
    public function resetDatabase(): void
    {
        DatabaseResetter::resetDatabase($this->kernel);
    }

    /**
     * @AfterScenario
     */
    public function resetSchema(): void
    {
        DatabaseResetter::resetSchema($this->kernel);
    }
}
