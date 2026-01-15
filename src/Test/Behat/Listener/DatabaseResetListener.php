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
            ScenarioTested::BEFORE => ['beforeScenario', BootConfigurationListener::BOOT_PRIORITY - 10],
            ExampleTested::BEFORE => ['beforeScenario', BootConfigurationListener::BOOT_PRIORITY - 10],
        ];
    }

    public function resetBeforeSuite(): void
    {
        $container = $this->symfonyKernel->getContainer();

        Configuration::boot(
            $container->get('.zenstruck_foundry.configuration') // @phpstan-ignore argument.type
        );

        ResetDatabaseManager::resetBeforeFirstTest($this->symfonyKernel);

        Configuration::shutdown();
    }

    public function beforeScenario(): void
    {
        if (!$this->hasResetBeforeFirstTest) {
            $this->hasResetBeforeFirstTest = true;
        }

        if (DatabaseResetMode::SCENARIO === $this->resetMode) {
            ResetDatabaseManager::resetBeforeEachTest($this->symfonyKernel);
        }
    }
}
