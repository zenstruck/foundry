<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Unit\Test\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterScenarioSetup;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Tester\Setup\Setup;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zenstruck\Foundry\Test\Behat\Listener\LoadFixturesListener;

final class LoadFixturesListenerTest extends TestCase
{
    #[Test]
    public function it_throws_when_multiple_withFixture_tags_on_scenario(): void
    {
        $listener = $this->createListenerWithMockedKernel();
        $event = $this->createAfterScenarioSetupEvent(
            featureTags: [],
            scenarioTags: ['withFixture(Fixture1)', 'withFixture(Fixture2)']
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Multiple @withFixture tags found: you can only load one fixture per scenario.');

        $listener->loadFixtureIfTagged($event);
    }

    #[Test]
    public function it_throws_when_multiple_withFixture_tags_from_feature_and_scenario(): void
    {
        $listener = $this->createListenerWithMockedKernel();
        $event = $this->createAfterScenarioSetupEvent(
            featureTags: ['withFixture(FeatureFixture)'],
            scenarioTags: ['withFixture(ScenarioFixture)']
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Multiple @withFixture tags found: you can only load one fixture per scenario.');

        $listener->loadFixtureIfTagged($event);
    }

    #[Test]
    public function it_does_nothing_when_no_withFixture_tags(): void
    {
        $this->expectNotToPerformAssertions();

        $listener = $this->createListenerWithMockedKernel();
        $event = $this->createAfterScenarioSetupEvent(
            featureTags: ['someOtherTag'],
            scenarioTags: ['anotherTag']
        );

        $listener->loadFixtureIfTagged($event);
    }

    #[Test]
    public function it_does_nothing_when_no_tags_at_all(): void
    {
        $this->expectNotToPerformAssertions();

        $listener = $this->createListenerWithMockedKernel();
        $event = $this->createAfterScenarioSetupEvent(
            featureTags: [],
            scenarioTags: []
        );

        $listener->loadFixtureIfTagged($event);
    }

    private function createListenerWithMockedKernel(): LoadFixturesListener
    {
        $kernel = $this->createStub(\Symfony\Component\HttpKernel\KernelInterface::class);

        return new LoadFixturesListener($kernel);
    }

    /**
     * @param list<string> $featureTags
     * @param list<string> $scenarioTags
     */
    private function createAfterScenarioSetupEvent(array $featureTags, array $scenarioTags): AfterScenarioSetup
    {
        $scenario = new ScenarioNode('Test Scenario', $scenarioTags, [], 'scenario', 10);

        $feature = new FeatureNode(
            'Test Feature',
            'Description',
            $featureTags,
            null,
            [$scenario],
            'feature',
            'en',
            '/path/to/test.feature',
            1
        );

        $environment = $this->createStub(Environment::class);
        $setup = $this->createStub(Setup::class);

        return new AfterScenarioSetup($environment, $feature, $scenario, $setup);
    }
}
