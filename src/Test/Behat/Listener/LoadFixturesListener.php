<?php

namespace Zenstruck\Foundry\Test\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterScenarioSetup;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Gherkin\Node\TaggedNodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Story\FixtureStoryResolver;
use Zenstruck\Foundry\Test\Behat\BehatTagParser;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class LoadFixturesListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly KernelInterface $symfonyKernel,
        private readonly BehatTagParser $tagParser,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScenarioTested::AFTER_SETUP => 'loadFixtureIfTagged',
            ExampleTested::AFTER_SETUP => 'loadFixtureIfTagged',
        ];
    }

    public function loadFixtureIfTagged(AfterScenarioSetup $event): void
    {
        $scenario = $event->getScenario();

        if (!$scenario instanceof TaggedNodeInterface) {
            return;
        }

        $tags = $scenario->getTags();

        $fixtureName = $this->tagParser->parseFixtureName($tags);

        if (null === $fixtureName) {
            return;
        }

        $container = $this->symfonyKernel->getContainer();

        /** @var FixtureStoryResolver $fixtureStoryResolver */
        $fixtureStoryResolver = $container->get('.zenstruck_foundry.story.fixture_resolver');

        $storyClass = $fixtureStoryResolver->resolve($fixtureName);
        $storyClass::load();
    }
}
