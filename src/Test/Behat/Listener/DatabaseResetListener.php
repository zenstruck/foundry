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

namespace Zenstruck\Foundry\Test\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Gherkin\Node\TaggedNodeInterface;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;
use Zenstruck\Foundry\StoryRegistry;
use Zenstruck\Foundry\Test\Behat\DatabaseResetMode;
use Zenstruck\Foundry\Test\Behat\Exception\DamaNativeExtensionIncompatibility;
use Zenstruck\Foundry\Test\Behat\Exception\InvalidResetDbTag;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class DatabaseResetListener implements EventSubscriberInterface
{
    private const RESET_DB_TAG = 'resetDB';
    private const NO_RESET_DB_TAG = 'noResetDB';

    public function __construct(
        private readonly KernelInterface $symfonyKernel,
        private readonly DatabaseResetMode $resetMode,
        private readonly bool $damaSupportEnabled,
        private readonly bool $damaNativeExtensionIsEnabled,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ExerciseCompleted::BEFORE => ['resetBeforeSuite', -10], // -10 because it should occur after Dama
            ExerciseCompleted::AFTER => 'disableStaticConnection',

            FeatureTested::BEFORE => [
                ['validateFeature', 10],
                ['resetDatabaseIfNeeded']
            ],
            ScenarioTested::BEFORE => [
                ['validateScenario', 10],
                ['resetDatabaseIfNeeded']
            ],
            ExampleTested::BEFORE => [
                ['validateScenario', 10],
                ['resetDatabaseIfNeeded']
            ],

            // a shutdown is needed after each scenario to ensure StoriesRegistry is reset
            ScenarioTested::AFTER => 'shutdownFoundryAfterScenario',
            ExampleTested::AFTER => 'shutdownFoundryAfterScenario',
        ];
    }

    public function resetBeforeSuite(): void
    {
        if ($this->damaSupportEnabled) {
            StaticDriver::setKeepStaticConnections(true);
        }

        if (DatabaseResetMode::MANUAL === $this->resetMode) {
            return;
        }

        ResetDatabaseManager::resetBeforeFirstTest($this->symfonyKernel);
    }

    public function disableStaticConnection(): void
    {
        if ($this->damaSupportEnabled) {
            StaticDriver::setKeepStaticConnections(false);
        }
    }

    public function validateFeature(BeforeFeatureTested $event): void
    {
        if ($this->hasResetDbTag($event) && $this->resetMode === DatabaseResetMode::FEATURE) {
            throw InvalidResetDbTag::resetDbOnFeatureWithFeatureMode($event);
        }
    }

    public function validateScenario(BeforeScenarioTested $event): void
    {
        if ($this->hasResetDbTag($event) && $this->resetMode === DatabaseResetMode::SCENARIO) {
            throw InvalidResetDbTag::resetDbOnScenarioWithScenarioMode($event);
        }

        if ($this->hasResetDbTag($event) && $this->hasNoResetDbTag($event)) {
            throw InvalidResetDbTag::bothTagsUsed($event);
        }
    }

    public function resetDatabaseIfNeeded(BeforeFeatureTested|BeforeScenarioTested $event): void
    {
        if (!$this->shouldResetDB($event)) {
            return;
        }

        $this->resetObjectRegistry();

        // when the DB is reset, any stories should be able to reload
        StoryRegistry::reset();

        if (!ResetDatabaseManager::databaseHasBeenResetBeforeFirstTest()) {
            ResetDatabaseManager::resetBeforeFirstTest($this->symfonyKernel);
        }

        if ($this->damaSupportEnabled) {
            StaticDriver::rollBack();
            StaticDriver::beginTransaction();

            return;
        }

        ResetDatabaseManager::resetBeforeEachTest($this->symfonyKernel);
    }

    private function shouldResetDB(BeforeFeatureTested|BeforeScenarioTested $event): bool
    {
        if ($this->hasNoResetDbTag($event)) {
            return false;
        }

        if ($this->hasResetDbTag($event)) {
            return true;
        }

        return $event instanceof BeforeScenarioTested && $this->resetMode === DatabaseResetMode::SCENARIO
            || $event instanceof BeforeFeatureTested && $this->resetMode === DatabaseResetMode::FEATURE;
    }

    public function shutdownFoundryAfterScenario(): void
    {
        if (DatabaseResetMode::SCENARIO !== $this->resetMode) {
            return;
        }

        $this->resetObjectRegistry();
        Configuration::shutdown();
    }

    private function hasResetDbTag(BeforeFeatureTested|BeforeScenarioTested $event): bool
    {
        $node = $event instanceof BeforeFeatureTested ? $event->getFeature() : $event->getScenario();

        if (!$node instanceof TaggedNodeInterface) {
            return false;
        }

        $hasResetDbTag = $node->hasTag(self::RESET_DB_TAG);

        if (!$hasResetDbTag) {
            return false;
        }

        if ($this->resetMode === DatabaseResetMode::SCENARIO) {
            throw InvalidResetDbTag::resetDbWithScenarioMode($event);
        }

        return true;
    }

    private function hasNoResetDbTag(BeforeFeatureTested|BeforeScenarioTested $event): bool
    {
        $node = $event instanceof BeforeFeatureTested ? $event->getFeature() : $event->getScenario();

        if (!$node instanceof TaggedNodeInterface) {
            return false;
        }

        $hasNoResetDbTag = $node->hasTag(self::NO_RESET_DB_TAG);

        if (!$hasNoResetDbTag) {
            return false;
        }

        if ($this->damaNativeExtensionIsEnabled) {
            throw DamaNativeExtensionIncompatibility::withNoResetDbTag();
        }

        return match($this->resetMode) {
            DatabaseResetMode::MANUAL => throw InvalidResetDbTag::noResetDbWithManualMode($event),
            DatabaseResetMode::FEATURE => throw InvalidResetDbTag::noResetDbWithFeatureMode($event),
            default => true,
        };
    }

    private function resetObjectRegistry(): void
    {
        $this->objectRegistry()->reset();
    }

    private function objectRegistry(): ObjectRegistry
    {
        return $this->symfonyKernel->getContainer()->get('.zenstruck_foundry.behat.object_registry'); // @phpstan-ignore return.type
    }
}
