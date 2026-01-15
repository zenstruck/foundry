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

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;
use Zenstruck\Foundry\Test\Behat\Config\DatabaseResetMode;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class DatabaseResetListener implements EventSubscriberInterface
{
    private bool $hasResetBeforeFirstTest = false;

    public function __construct(
        private readonly KernelInterface $symfonyKernel,
        private readonly DatabaseResetMode $resetMode,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ExerciseCompleted::BEFORE => 'resetBeforeSuite',
            FeatureTested::BEFORE => 'beforeFeature',
            ScenarioTested::BEFORE => 'beforeScenario',
            ExampleTested::BEFORE => 'beforeScenario',

            // a shutdown is needed after each scenario/feature to ensure StoriesRegistry is reset
            FeatureTested::AFTER => 'shutdownFoundryAfterFeature',
            ScenarioTested::AFTER => 'shutdownFoundryAfterScenario',
            ExampleTested::AFTER => 'shutdownFoundryAfterScenario',
        ];
    }

    public function resetBeforeSuite(): void
    {
        ResetDatabaseManager::resetBeforeFirstTest($this->symfonyKernel);
    }

    public function beforeFeature(): void
    {
        if (DatabaseResetMode::FEATURE !== $this->resetMode) {
            return;
        }

        if (!$this->hasResetBeforeFirstTest) {
            $this->hasResetBeforeFirstTest = true;
        }

        ResetDatabaseManager::resetBeforeEachTest($this->symfonyKernel);
    }

    public function beforeScenario(): void
    {
        if (DatabaseResetMode::SCENARIO !== $this->resetMode) {
            return;
        }

        if (!$this->hasResetBeforeFirstTest) {
            $this->hasResetBeforeFirstTest = true;
        }

        ResetDatabaseManager::resetBeforeEachTest($this->symfonyKernel);
    }

    public function shutdownFoundryAfterFeature(): void
    {
        $this->symfonyKernel->getContainer()->get('.zenstruck_foundry.behat.object_registry')->reset(); // @phpstan-ignore method.notFound
        Configuration::shutdown();
    }

    public function shutdownFoundryAfterScenario(): void
    {
        if (DatabaseResetMode::SCENARIO !== $this->resetMode) {
            return;
        }

        $this->symfonyKernel->getContainer()->get('.zenstruck_foundry.behat.object_registry')->reset(); // @phpstan-ignore method.notFound
        Configuration::shutdown();
    }
}
