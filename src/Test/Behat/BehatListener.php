<?php

namespace Zenstruck\Foundry\Test\Behat;

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
        $container = $this->symfonyKernel->getContainer();

        $container->get('.zenstruck_foundry.behat.object_registry')->reset(); // @phpstan-ignore method.notFound

        Configuration::boot(
            $container->get('.zenstruck_foundry.configuration') // @phpstan-ignore argument.type
        );
    }

    public function shutdownFoundry(): void
    {
        Configuration::shutdown();
    }
}
