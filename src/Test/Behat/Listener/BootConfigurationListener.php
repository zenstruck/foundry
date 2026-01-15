<?php

namespace Zenstruck\Foundry\Test\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class BootConfigurationListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly KernelInterface $symfonyKernel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ExerciseCompleted::BEFORE => ['bootFoundry', 100],
            FeatureTested::BEFORE => ['bootFoundry', 100],
            ScenarioTested::BEFORE => ['bootFoundry', 100],
            ExampleTested::BEFORE => ['bootFoundry', 100],

            ExerciseCompleted::AFTER => ['shutdownFoundry', -100],
        ];
    }

    public function bootFoundry(): void
    {
        if (Configuration::isBooted()) {
            return;
        }

        Configuration::boot(
            fn() => $this->symfonyKernel->getContainer()->get('.zenstruck_foundry.configuration') // @phpstan-ignore argument.type
        );
    }

    public function shutdownFoundry(): void
    {
        Configuration::shutdown();
    }
}
