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

namespace Zenstruck\Foundry\Tests\Integration\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterScenarioSetup;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Tester\Setup\Setup;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Behat\Exception\ObjectAlreadyRegistered;
use Zenstruck\Foundry\Test\Behat\Listener\LoadFixturesListener;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Behat\BehatTestKernel;
use Zenstruck\Foundry\Tests\Fixture\Entity\Category;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\Category\CategoryFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/** @requires PHP 9 */
#[RequiresPhp('9')]
final class LoadFixturesListenerTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    protected function setUp(): void
    {
        $this->objectRegistry()->reset();
    }

    /**
     * @param list<string> $featureTags
     * @param list<string> $scenarioTags
     */
    #[Test]
    #[DataProvider('noFixtureLoadedProvider')]
    public function it_does_not_load_fixtures(array $featureTags, array $scenarioTags): void
    {
        $listener = $this->createListener();
        $event = $this->createAfterScenarioSetupEvent($featureTags, $scenarioTags);

        $listener->loadFixtureIfTagged($event);

        CategoryFactory::assert()->count(0);
    }

    public static function noFixtureLoadedProvider(): iterable
    {
        yield 'no tags' => [[], []];
        yield 'no fixture tags' => [['someTag', 'anotherTag'], []];
        yield 'invalid tag: tag without parentheses' => [[], ['withFixture']];
        yield 'invalid tag: tag with empty parentheses' => [[], ['withFixture()']];
        yield 'invalid tag: tag with wrong prefix' => [[], ['loadFixture(behat-category)']];
        yield 'invalid tag: tag with extra closing parenthesis' => [[], ['withFixture(behat-category))']];
    }

    /**
     * @param list<string> $featureTags
     * @param list<string> $scenarioTags
     */
    #[Test]
    #[DataProvider('singleCategoryFixtureLoadedProvider')]
    public function it_loads_single_category_fixture(array $featureTags, array $scenarioTags, bool $isOutline = false): void
    {
        $listener = $this->createListener();
        $event = $isOutline
            ? $this->createAfterScenarioSetupEventWithOutline($scenarioTags)
            : $this->createAfterScenarioSetupEvent($featureTags, $scenarioTags);

        $listener->loadFixtureIfTagged($event);

        CategoryFactory::assert()->count(1);
        $this->objectRegistry()->has(Category::class, 'category fixture');
    }

    public static function singleCategoryFixtureLoadedProvider(): iterable
    {
        yield 'from scenario tag' => [[], ['withFixture(behat-category)']];
        yield 'from feature tag' => [['withFixture(behat-category)'], []];
        yield 'outline example scenario' => [[], ['withFixture(behat-category)'], true];
        yield 'combined feature and scenario tags' => [['withFixture(behat-category)'], ['withFixture(behat-category)']];
    }

    #[Test]
    public function it_loads_fixture_group(): void
    {
        $listener = $this->createListener();
        $event = $this->createAfterScenarioSetupEvent([], ['withFixture(behat-stories)']);

        $listener->loadFixtureIfTagged($event);

        CategoryFactory::assert()->count(2);
        $this->objectRegistry()->has(Category::class, 'category fixture');
        $this->objectRegistry()->has(Contact::class, 'john-doe');
    }

    #[Test]
    public function it_throws_if_states_name_conflict_in_stories(): void
    {
        $this->expectException(ObjectAlreadyRegistered::class);
        $this->expectExceptionMessage('Object "duplicate" is already registered for class "'.Contact::class);

        $listener = $this->createListener();
        $event = $this->createAfterScenarioSetupEvent([], ['withFixture(conflict-test)']);

        $listener->loadFixtureIfTagged($event);
    }

    protected static function getKernelClass(): string
    {
        return BehatTestKernel::class;
    }

    private function createListener(): LoadFixturesListener
    {
        return new LoadFixturesListener(self::$kernel ?? self::bootKernel());
    }

    /**
     * @param list<string> $featureTags
     * @param list<string> $scenarioTags
     */
    private function createAfterScenarioSetupEvent(array $featureTags, array $scenarioTags): AfterScenarioSetup
    {
        $scenario = new ScenarioNode('Test Scenario', $scenarioTags, [], 'scenario', 10);

        $feature = new FeatureNode(
            'Test Feature', 'Description', $featureTags, null, [$scenario], 'feature', 'en', '/path/to/test.feature', 1
        );

        $environment = $this->createStub(Environment::class);
        $setup = $this->createStub(Setup::class);

        return new AfterScenarioSetup($environment, $feature, $scenario, $setup);
    }

    /**
     * @param list<string> $outlineTags
     */
    private function createAfterScenarioSetupEventWithOutline(array $outlineTags): AfterScenarioSetup
    {
        $outline = new OutlineNode('Test Outline', $outlineTags, [], [], 'outline', 10);

        $feature = new FeatureNode(
            'Test Feature', 'Description', [], null, [$outline], 'feature', 'en', '/path/to/test.feature', 1
        );

        $environment = $this->createStub(Environment::class);
        $setup = $this->createStub(Setup::class);

        return new AfterScenarioSetup($environment, $feature, $outline, $setup);
    }

    private function objectRegistry(): ObjectRegistry
    {
        return self::getContainer()->get('.zenstruck_foundry.behat.object_registry'); // @phpstan-ignore return.type
    }
}
