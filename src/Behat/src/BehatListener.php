<?php

namespace Zenstruck\Foundry\Behat;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;

final class BehatListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly KernelInterface $symfonyKernel
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScenarioTested::BEFORE => 'bootFoundry',
            ExampleTested::BEFORE => 'bootFoundry',
            ScenarioTested::AFTER => 'shutdownFoundry',
            ExampleTested::AFTER => 'shutdownFoundry',
        ];
    }

    public function bootFoundry(): void
    {
        Configuration::boot(
            $this->symfonyKernel->getContainer()->get('.zenstruck_foundry.configuration')
        );
    }

    public function shutdownFoundry(): void
    {
        Configuration::shutdown();
    }
}
