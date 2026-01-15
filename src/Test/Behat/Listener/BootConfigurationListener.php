<?php

namespace Zenstruck\Foundry\Test\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Gherkin\Node\TaggedNodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Story\FixtureStoryResolver;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class BootConfigurationListener implements EventSubscriberInterface
{
    public const BOOT_PRIORITY = 100;

    public function __construct(
        private readonly KernelInterface $symfonyKernel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScenarioTested::BEFORE => ['bootFoundry', self::BOOT_PRIORITY],
            ExampleTested::BEFORE => ['bootFoundry', self::BOOT_PRIORITY],
            ScenarioTested::AFTER => ['shutdownFoundry', -100],
            ExampleTested::AFTER => ['shutdownFoundry', -100],
        ];
    }

    public function bootFoundry(BeforeScenarioTested $event): void
    {
        $container = $this->symfonyKernel->getContainer();

        Configuration::boot(
            $container->get('.zenstruck_foundry.configuration') // @phpstan-ignore argument.type
        );

        $container->get('.zenstruck_foundry.behat.object_registry')->reset(); // @phpstan-ignore method.notFound
    }

    public function shutdownFoundry(): void
    {
        Configuration::shutdown();
    }
}
