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

        // the newer Gherkin modes keep the "@" prefix, the legacy one strips it
        /** @param list<string> $tags @return list<string> */
        $bare = static fn(array $tags): array => \array_values(\array_map(static fn(string $tag) => \ltrim($tag, '@'), $tags));

        $tags = $bare($feature->getTags());

        if ($scenario instanceof TaggedNodeInterface) {
            $tags = [...$tags, ...$bare($scenario->getTags())];
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
        return \array_values(\array_filter(\array_map(
            static fn(string $tag): ?string => \preg_match(self::FIXTURE_TAG_PATTERN, $tag, $matches) ? $matches[1] : null,
            $tags
        ), static fn(?string $fixtureName): bool => null !== $fixtureName));
    }

    private function fixtureStoryResolver(): FixtureStoryResolver
    {
        return $this->symfonyKernel->getContainer()->get('.zenstruck_foundry.story.fixture_resolver'); // @phpstan-ignore return.type
    }
}
