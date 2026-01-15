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
use Zenstruck\Foundry\Test\Behat\Config\DatabaseResetMode;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class DatabaseResetListener implements EventSubscriberInterface
{
    private const RESET_DB_TAG = 'resetDB';

    public function __construct(
        private readonly KernelInterface $symfonyKernel,
        private readonly DatabaseResetMode $resetMode,
        private readonly bool $damaSupportEnabled = false,
    ) {
        $this->detectDAMAListenerConflict();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ExerciseCompleted::BEFORE => 'resetBeforeSuite',
            ExerciseCompleted::AFTER => 'disableStaticConnection',

            FeatureTested::BEFORE => 'beforeFeature',
            ScenarioTested::BEFORE => 'beforeScenario',
            ExampleTested::BEFORE => 'beforeScenario',

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

        ResetDatabaseManager::resetBeforeFirstTest($this->symfonyKernel);
    }

    public function disableStaticConnection(): void
    {
        if ($this->damaSupportEnabled) {
            StaticDriver::setKeepStaticConnections(false);
        }
    }

    public function beforeFeature(BeforeFeatureTested $event): void
    {
        if (!$this->hasResetTag($event) && DatabaseResetMode::FEATURE !== $this->resetMode) {
            return;
        }

        $this->resetDatabase();
    }

    public function beforeScenario(BeforeScenarioTested $event): void
    {
        $hasResetTag = $this->hasResetTag($event);
        if (!$hasResetTag && DatabaseResetMode::SCENARIO !== $this->resetMode) {
            return;
        }

        $this->resetDatabase();
    }

    public function shutdownFoundryAfterScenario(): void
    {
        if (DatabaseResetMode::SCENARIO !== $this->resetMode) {
            return;
        }

        $this->resetObjectRegistry();
        Configuration::shutdown();
    }

    private function detectDAMAListenerConflict(): void
    {
        // todo: il y a un paramètre "extensions" dans le container de behat qui permet de lister les extensions actives
    }

    private function hasResetTag(BeforeFeatureTested|BeforeScenarioTested $event): bool
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
            // todo: tester les erreurs !
            // todo: ajouter des infos concernant le fichier de features
            throw new \LogicException("Cannot use \"@resetDB\" tag with database_reset_mode set as \"{$this->resetMode->value}\".");
        }

        if ($this->resetMode === DatabaseResetMode::FEATURE && $event instanceof BeforeFeatureTested) {
            throw new \LogicException("Cannot use \"@resetDB\" tag on a feature with database_reset_mode set as \"{$this->resetMode->value}\".");
        }

        return true;
    }

    private function resetDatabase(): void
    {
        $this->resetObjectRegistry();

        // when the DB is reset, any stories should be able to reload
        StoryRegistry::reset();

        if ($this->damaSupportEnabled) {
            StaticDriver::rollBack();
            StaticDriver::beginTransaction();

            return;
        }

        ResetDatabaseManager::resetBeforeEachTest($this->symfonyKernel);
    }

    private function resetObjectRegistry(): void
    {
        $this->symfonyKernel->getContainer()->get('.zenstruck_foundry.behat.object_registry')->reset(); // @phpstan-ignore method.notFound
    }
}
