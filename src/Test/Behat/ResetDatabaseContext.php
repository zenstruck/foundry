<?php

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Behat\Context\Context;
use Psr\Container\ContainerInterface;
use Zenstruck\Foundry\Test\DatabaseResetter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ResetDatabaseContext implements Context
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @BeforeSuite
     */
    public function resetDatabase(): void
    {
        DatabaseResetter::resetDatabase($this->container->get('kernel'));
    }

    /**
     * @BeforeScenario
     */
    public function resetSchema(): void
    {
        DatabaseResetter::resetSchema($this->container->get('kernel'));
    }
}
