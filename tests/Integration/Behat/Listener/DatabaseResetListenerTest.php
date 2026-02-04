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

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Testwork\Environment\StaticEnvironment;
use Behat\Testwork\Suite\GenericSuite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Behat\DatabaseResetMode;
use Zenstruck\Foundry\Test\Behat\Exception\DamaNativeExtensionIncompatibility;
use Zenstruck\Foundry\Test\Behat\Exception\InvalidResetDbTag;
use Zenstruck\Foundry\Test\Behat\Listener\DatabaseResetListener;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Tests\Fixture\Behat\BehatTestKernel;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;
use Zenstruck\Foundry\Tests\Integration\RequiresORM;

/** @requires PHP 9 */
#[RequiresPhp('9')]
final class DatabaseResetListenerTest extends KernelTestCase
{
    use Factories, RequiresORM, ResetDatabase;

    protected function setUp(): void
    {
        $this->objectRegistry()->reset();
    }

    /**
     * @param list<string>             $tags
     * @param class-string<\Throwable> $exceptionClass
     */
    #[Test]
    #[DataProvider('validateFeatureExceptionProvider')]
    public function it_throws_exception_on_validate_feature(
        DatabaseResetMode $mode,
        array $tags,
        string $exceptionClass,
        string $exceptionMessage,
    ): void {
        $listener = $this->createListener($mode);
        $event = $this->createFeatureEvent($tags);

        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);

        $listener->validateFeature($event);
    }

    public static function validateFeatureExceptionProvider(): iterable
    {
        yield 'resetDB tag on feature with feature mode' => [
            DatabaseResetMode::FEATURE,
            ['resetDB'],
            InvalidResetDbTag::class,
            'Cannot use "@resetDB" tag on a feature with database_reset_mode set as "feature".',
        ];
    }

    /**
     * @param list<string>             $tags
     * @param class-string<\Throwable> $exceptionClass
     */
    #[Test]
    #[DataProvider('validateScenarioExceptionProvider')]
    public function it_throws_exception_on_validate_scenario(
        DatabaseResetMode $mode,
        array $tags,
        string $exceptionClass,
        string $exceptionMessage,
    ): void {
        $listener = $this->createListener($mode);
        $event = $this->createScenarioEvent($tags);

        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);

        $listener->validateScenario($event);
    }

    public static function validateScenarioExceptionProvider(): iterable
    {
        yield 'resetDB tag on scenario with scenario mode' => [
            DatabaseResetMode::SCENARIO,
            ['resetDB'],
            InvalidResetDbTag::class,
            'Cannot use "@noResetDB" tag with database_reset_mode set as "manual".',
        ];

        yield 'both resetDB and noResetDB tags on scenario' => [
            DatabaseResetMode::MANUAL,
            ['resetDB', 'noResetDB'],
            InvalidResetDbTag::class,
            'Cannot use "@noResetDB" tag with database_reset_mode set as "manual".',
        ];
    }

    /**
     * @param list<string> $tags
     */
    #[Test]
    #[DataProvider('resetDatabaseIfNeededBehaviorProvider')]
    public function it_resets_database_and_registries_when_needed(
        DatabaseResetMode $mode,
        string $eventType,
        array $tags,
        bool $shouldReset,
    ): void {
        $listener = $this->createListener($mode, damaSupportEnabled: true);
        $objectRegistry = $this->objectRegistry();

        $testObject = GenericEntityFactory::createOne();
        GenericEntityFactory::assert()->count(1);

        $objectRegistry->store($testObject, 'test-object');
        self::assertTrue($objectRegistry->isStored($testObject));

        $event = 'feature' === $eventType ? $this->createFeatureEvent($tags) : $this->createScenarioEvent($tags);

        $listener->resetDatabaseIfNeeded($event);

        if ($shouldReset) {
            self::assertFalse($objectRegistry->isStored($testObject));
            GenericEntityFactory::assert()->count(0);
        } else {
            self::assertTrue($objectRegistry->isStored($testObject));
            GenericEntityFactory::assert()->count(1);
        }
    }

    public static function resetDatabaseIfNeededBehaviorProvider(): iterable
    {
        yield 'scenario mode resets on scenario without tags' => [
            DatabaseResetMode::SCENARIO,
            'scenario',
            [],
            true,
        ];

        yield 'scenario mode does not reset with noResetDB tag' => [
            DatabaseResetMode::SCENARIO,
            'scenario',
            ['noResetDB'],
            false,
        ];

        yield 'feature mode resets on feature without tags' => [
            DatabaseResetMode::FEATURE,
            'feature',
            [],
            true,
        ];

        yield 'manual mode does not reset without resetDB tag' => [
            DatabaseResetMode::MANUAL,
            'scenario',
            [],
            false,
        ];

        yield 'manual mode resets with resetDB tag' => [
            DatabaseResetMode::MANUAL,
            'scenario',
            ['resetDB'],
            true,
        ];

        yield 'scenario mode does not reset on feature' => [
            DatabaseResetMode::SCENARIO,
            'feature',
            [],
            false,
        ];

        yield 'feature mode does not reset on scenario' => [
            DatabaseResetMode::FEATURE,
            'scenario',
            [],
            false,
        ];
    }

    /**
     * @param list<string>             $tags
     * @param class-string<\Throwable> $exceptionClass
     */
    #[Test]
    #[DataProvider('resetDatabaseIfNeededExceptionProvider')]
    public function it_throws_exception_on_reset_database_if_needed(
        DatabaseResetMode $mode,
        array $tags,
        string $exceptionClass,
        string $exceptionMessage,
        bool $damaSupportEnabled = false,
        bool $damaNativeExtensionIsEnabled = false,
    ): void {
        $listener = $this->createListener($mode, $damaSupportEnabled, $damaNativeExtensionIsEnabled);
        $event = $this->createScenarioEvent($tags);

        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);

        $listener->resetDatabaseIfNeeded($event);
    }

    public static function resetDatabaseIfNeededExceptionProvider(): iterable
    {
        yield 'noResetDB tag with dama native extension' => [
            DatabaseResetMode::SCENARIO,
            ['noResetDB'],
            DamaNativeExtensionIncompatibility::class,
            'Cannot use "@noResetDB" with native Behat extension for "dama/doctrine-test-bundle".',
            false,
            true,
        ];

        yield 'noResetDB tag with manual mode' => [
            DatabaseResetMode::MANUAL,
            ['noResetDB'],
            InvalidResetDbTag::class,
            'Cannot use "@noResetDB" tag with database_reset_mode set as "manual".',
        ];

        yield 'noResetDB tag with feature mode' => [
            DatabaseResetMode::FEATURE,
            ['noResetDB'],
            InvalidResetDbTag::class,
            'Cannot use "@noResetDB" with database_reset_mode set as "feature".',
        ];
    }

    /**
     * @param list<string> $tags
     */
    #[Test]
    #[DataProvider('validateScenarioNoExceptionProvider')]
    public function it_does_not_throw_on_validate_scenario(
        DatabaseResetMode $mode,
        array $tags,
    ): void {
        $this->expectNotToPerformAssertions();

        $listener = $this->createListener($mode);
        $event = $this->createScenarioEvent($tags);

        $listener->validateScenario($event);
    }

    public static function validateScenarioNoExceptionProvider(): iterable
    {
        yield 'resetDB tag with manual mode' => [
            DatabaseResetMode::MANUAL,
            ['resetDB'],
        ];

        yield 'no tags with scenario mode' => [
            DatabaseResetMode::SCENARIO,
            [],
        ];
    }

    #[Test]
    public function it_does_not_throw_for_no_reset_d_b_tag_with_scenario_mode(): void
    {
        $this->expectNotToPerformAssertions();

        $listener = $this->createListener(DatabaseResetMode::SCENARIO);
        $event = $this->createScenarioEvent(['noResetDB']);

        $listener->resetDatabaseIfNeeded($event);
    }

    #[Test]
    public function it_validates_feature_without_reset_d_b_tag_in_feature_mode(): void
    {
        $this->expectNotToPerformAssertions();

        $listener = $this->createListener(DatabaseResetMode::FEATURE);
        $event = $this->createFeatureEvent([]);

        $listener->validateFeature($event);
    }

    protected static function getKernelClass(): string
    {
        return BehatTestKernel::class;
    }

    private function createListener(
        DatabaseResetMode $mode,
        bool $damaSupportEnabled = false,
        bool $damaNativeExtensionIsEnabled = false,
    ): DatabaseResetListener {
        return new DatabaseResetListener(self::$kernel ?? self::bootKernel(), $mode, $damaSupportEnabled, $damaNativeExtensionIsEnabled);
    }

    private function objectRegistry(): ObjectRegistry
    {
        return self::getContainer()->get('.zenstruck_foundry.behat.object_registry'); // @phpstan-ignore return.type
    }

    private function createEnvironment(): StaticEnvironment
    {
        return new StaticEnvironment(new GenericSuite('default', ['paths' => ['/path/to']]));
    }

    /**
     * @param list<string> $tags
     */
    private function createFeatureEvent(array $tags): BeforeFeatureTested
    {
        $feature = new FeatureNode(
            'Test Feature',
            'Description',
            $tags,
            null,
            [],
            'feature',
            'en',
            '/path/to/test.feature',
            1
        );

        return new BeforeFeatureTested($this->createEnvironment(), $feature);
    }

    /**
     * @param list<string> $tags
     */
    private function createScenarioEvent(array $tags): BeforeScenarioTested
    {
        $scenario = new ScenarioNode('Test Scenario', $tags, [], 'scenario', 10);

        $feature = new FeatureNode(
            'Test Feature',
            'Description',
            [],
            null,
            [$scenario],
            'feature',
            'en',
            '/path/to/test.feature',
            1
        );

        return new BeforeScenarioTested($this->createEnvironment(), $feature, $scenario);
    }
}
