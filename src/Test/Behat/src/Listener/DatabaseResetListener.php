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

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Gherkin\Node\TaggedNodeInterface;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;
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

            // priorities: validation must run first, then the conditional shutdown must run
            // BEFORE BootConfigurationListener::bootFoundry (priority 100) so that the scenario
            // starts with a freshly booted Foundry, and the database reset itself runs last
            FeatureTested::BEFORE => [
                ['validateFeature', 200],
                ['shutdownFoundryIfDatabaseWillReset', 150],
                ['resetDatabaseIfNeeded'],
            ],
            ScenarioTested::BEFORE => [
                ['validateScenario', 200],
                ['shutdownFoundryIfDatabaseWillReset', 150],
                ['resetDatabaseIfNeeded'],
            ],
            ExampleTested::BEFORE => [
                ['validateScenario', 200],
                ['shutdownFoundryIfDatabaseWillReset', 150],
                ['resetDatabaseIfNeeded'],
            ],
        ];
    }

    public function resetBeforeSuite(): void
    {
        if ($this->damaSupportEnabled) {
            $this->assertDamaBundleIsRegistered();
            StaticDriver::setKeepStaticConnections(true);
        }

        // in pure manual mode the schema reset is deferred until the first @resetDB. With DAMA
        // support it must happen now: once a static connection holds its transaction, a deferred
        // DamaDatabaseResetter::resetBeforeFirstTest() would drop the database under an open
        // transaction (metadata-lock freeze on MySQL, connection kill on PostgreSQL) and reboot
        // the kernel in the middle of a scenario. @resetDB keeps its user-facing semantics: it
        // rolls the static transaction back.
        if (DatabaseResetMode::MANUAL === $this->resetMode && !$this->damaSupportEnabled) {
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
        $this->validateTags($event);

        if ($this->hasResetDbTag($event) && DatabaseResetMode::FEATURE === $this->resetMode) {
            throw InvalidResetDbTag::resetDbOnFeatureWithFeatureMode($event);
        }
    }

    public function validateScenario(BeforeScenarioTested $event): void
    {
        $this->validateTags($event);
    }

    /**
     * Shutting down Foundry resets the story registry (so stories can reload in the fresh
     * database), the persisted objects tracker and the faker. It must happen before the
     * database reset of the coming scenario/feature, and never for a @noResetDB scenario:
     * its named references and loaded stories must survive.
     */
    public function shutdownFoundryIfDatabaseWillReset(BeforeFeatureTested|BeforeScenarioTested $event): void
    {
        if (!$this->shouldResetDB($event)) {
            return;
        }

        $this->resetObjectRegistry();
        Configuration::shutdown();
    }

    public function resetDatabaseIfNeeded(BeforeFeatureTested|BeforeScenarioTested $event): void
    {
        if (!$this->shouldResetDB($event)) {
            return;
        }

        if (!ResetDatabaseManager::databaseHasBeenResetBeforeFirstTest()) {
            ResetDatabaseManager::resetBeforeFirstTest($this->symfonyKernel);
        }

        if ($this->damaSupportEnabled) {
            StaticDriver::rollBack();
            StaticDriver::beginTransaction();

            return;
        }

        // same guard as the PHPUnit integration: with DAMA's native Behat extension each
        // scenario is already wrapped in a transaction and the initial reset committed the
        // global stories — resetBeforeEachTest() would reload them inside the transaction,
        // duplicating the global state on every scenario
        if (ResetDatabaseManager::canSkipSchemaReset()) {
            return;
        }

        ResetDatabaseManager::resetBeforeEachTest($this->symfonyKernel);
    }

    private function validateTags(BeforeFeatureTested|BeforeScenarioTested $event): void
    {
        $hasResetDbTag = $this->hasResetDbTag($event);
        $hasNoResetDbTag = $this->hasNoResetDbTag($event);

        if ($hasResetDbTag && $hasNoResetDbTag) {
            throw InvalidResetDbTag::bothTagsUsed($event);
        }

        if ($hasResetDbTag && DatabaseResetMode::SCENARIO === $this->resetMode) {
            throw InvalidResetDbTag::resetDbWithScenarioMode($event);
        }

        if (!$hasNoResetDbTag) {
            return;
        }

        if ($this->damaNativeExtensionIsEnabled) {
            throw DamaNativeExtensionIncompatibility::withNoResetDbTag();
        }

        match ($this->resetMode) {
            DatabaseResetMode::MANUAL => throw InvalidResetDbTag::noResetDbWithManualMode($event),
            DatabaseResetMode::FEATURE => throw InvalidResetDbTag::noResetDbWithFeatureMode($event),
            default => null,
        };
    }

    private function shouldResetDB(BeforeFeatureTested|BeforeScenarioTested $event): bool
    {
        if ($this->hasNoResetDbTag($event)) {
            return false;
        }

        if ($this->hasResetDbTag($event)) {
            return true;
        }

        return $event instanceof BeforeScenarioTested && DatabaseResetMode::SCENARIO === $this->resetMode
            || $event instanceof BeforeFeatureTested && DatabaseResetMode::FEATURE === $this->resetMode;
    }

    private function hasResetDbTag(BeforeFeatureTested|BeforeScenarioTested $event): bool
    {
        return $this->hasTag($event, self::RESET_DB_TAG);
    }

    /**
     * Unlike @resetDB(whose feature-level and scenario-level meanings differ), a feature-level.
     * @noResetDB is inherited by every scenario of the feature.
     */
    private function hasNoResetDbTag(BeforeFeatureTested|BeforeScenarioTested $event): bool
    {
        if ($this->hasTag($event, self::NO_RESET_DB_TAG)) {
            return true;
        }

        return $event instanceof BeforeScenarioTested && self::nodeHasTag($event->getFeature(), self::NO_RESET_DB_TAG);
    }

    private function hasTag(BeforeFeatureTested|BeforeScenarioTested $event, string $tag): bool
    {
        $node = $event instanceof BeforeFeatureTested ? $event->getFeature() : $event->getScenario();

        return $node instanceof TaggedNodeInterface && self::nodeHasTag($node, $tag);
    }

    /**
     * Gherkin's legacy compatibility mode strips the "@" from tag names, the newer modes keep it
     * (GherkinCompatibilityMode::shouldRemoveTagPrefixChar()). The mode is the user's choice, so
     * compare on the bare name rather than depending on it.
     */
    private static function nodeHasTag(TaggedNodeInterface $node, string $tag): bool
    {
        return \in_array($tag, self::bareTags($node->getTags()), true);
    }

    /**
     * @param list<string> $tags
     *
     * @return list<string>
     */
    private static function bareTags(array $tags): array
    {
        return \array_map(static fn(string $tag) => \ltrim($tag, '@'), $tags);
    }

    /**
     * Without the bundle, DAMA's DBAL middleware is not wired: StaticDriver never opens a
     * static connection and rollBack()/beginTransaction() would be silent no-ops, letting
     * data leak from scenario to scenario without any isolation.
     */
    private function assertDamaBundleIsRegistered(): void
    {
        $this->symfonyKernel->boot();

        if (!array_any($this->symfonyKernel->getBundles(), static fn(BundleInterface $bundle) => $bundle instanceof DAMADoctrineTestBundle)) {
            throw new \LogicException('Foundry\'s DAMA support is enabled but DAMADoctrineTestBundle is not registered in the application kernel: no transaction isolation would happen. Register "DAMA\DoctrineTestBundle\DAMADoctrineTestBundle" for the test environment in "config/bundles.php".');
        }
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
