<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterScenarioSetup;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Gherkin\Node\TaggedNodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Story\FixtureStoryResolver;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class LoadFixturesListener implements EventSubscriberInterface
{
    private const FIXTURE_TAG_PATTERN = '/^withFixture\(([^)]+)\)$/';

    public function __construct(
        private readonly KernelInterface $symfonyKernel,
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
        $feature = $event->getFeature();

        $tags = [];

        if ($feature instanceof TaggedNodeInterface) {
            $tags = [...$tags, ...$feature->getTags()];
        }

        if ($scenario instanceof TaggedNodeInterface) {
            $tags = [...$tags, ...$scenario->getTags()];
        }

        if (!$tags) {
            return;
        }

        $fixtureNames = $this->parseFixtureName($tags);

        if ([] === $fixtureNames) {
            return;
        }

        foreach ($fixtureNames as $fixtureName) {
            $stories = $this->fixtureStoryResolver()->resolve($fixtureName);

            foreach ($stories as $storyClass) {
                $storyClass::load();
            }
        }
    }

    /**
     * @param  list<string> $tags
     * @return list<string>
     */
    private function parseFixtureName(array $tags): array
    {
        $fixtureNames = [];

        foreach ($tags as $tag) {
            if (\preg_match(self::FIXTURE_TAG_PATTERN, $tag, $matches)) {
                $fixtureNames[] = $matches[1];
            }
        }

        return $fixtureNames;
    }

    private function fixtureStoryResolver(): FixtureStoryResolver
    {
        return $this->symfonyKernel->getContainer()->get('.zenstruck_foundry.story.fixture_resolver'); // @phpstan-ignore return.type
    }
}
