<?php

namespace Zenstruck\Foundry\Test\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;

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
            ExerciseCompleted::AFTER => ['shutdownFoundry', -100],

            FeatureTested::BEFORE => ['bootFoundry', 100],
            FeatureTested::AFTER => ['shutdownFoundryAfterFeature', -100],

            ScenarioTested::BEFORE => ['bootFoundry', 100],
            ExampleTested::BEFORE => ['bootFoundry', 100],
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

    /**
     * In any case, we want to shutdown Foundry after each feature:
     * - to reset the object registry
     * - to reset the story registry
     */
    public function shutdownFoundryAfterFeature(): void
    {
        $this->objectRegistry()->reset();
        Configuration::shutdown();
    }

    private function objectRegistry(): ObjectRegistry
    {
        return $this->symfonyKernel->getContainer()->get('.zenstruck_foundry.behat.object_registry'); // @phpstan-ignore return.type
    }
}
