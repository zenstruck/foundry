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

namespace Zenstruck\Foundry\PHPUnit;

use PHPUnit\Event;
use PHPUnit\Metadata\Version\ConstraintRequirement;
use PHPUnit\Runner;
use PHPUnit\TextUI;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\PHPUnit\DataProvider\BootFoundryOnDataProviderMethodCalled;
use Zenstruck\Foundry\PHPUnit\DataProvider\DataProviderSubscriberInterface;
use Zenstruck\Foundry\PHPUnit\DataProvider\ShutdownFoundryOnDataProviderMethodFinished;
use Zenstruck\Foundry\PHPUnit\DataProvider\TriggerDataProviderPersistenceOnTestPrepared;
use Zenstruck\Foundry\PHPUnit\ResetDatabase\ResetDatabaseOnTestPrepared;
use Zenstruck\Foundry\PHPUnit\ResetDatabase\ResetDatabaseOnTestSuiteStarted;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
if (\interface_exists(Runner\Extension\Extension::class)) {
    final class FoundryExtension implements Runner\Extension\Extension
    {
        private static bool $enabled = false;

        public function bootstrap(
            TextUI\Configuration\Configuration $configuration,
            Runner\Extension\Facade $facade,
            Runner\Extension\ParameterCollection $parameters,
        ): void {
            // shutdown Foundry if for some reason it has been booted before
            if (Configuration::isBooted()) {
                Configuration::shutdown();
            }

            // ⚠️ order matters within each event
            $subscribers = [
                Event\TestSuite\Started::class => [new ResetDatabaseOnTestSuiteStarted()],
                Event\Test\DataProviderMethodCalled::class => [new BootFoundryOnDataProviderMethodCalled()],
                Event\Test\DataProviderMethodFinished::class => [new ShutdownFoundryOnDataProviderMethodFinished()],
                Event\Test\Prepared::class => [
                    new BootFoundryOnTestPrepared(),
                    new EnableInMemoryOnTestPrepared(),
                    new ResetDatabaseOnTestPrepared(),
                    new BuildStoryOnTestPrepared(),
                    new TriggerDataProviderPersistenceOnTestPrepared(),
                ],
                Event\Test\Finished::class => [new ShutdownFoundryOnTestFinished()],
                Event\TestRunner\Finished::class => [new DisplayFakerSeedOnApplicationFinished()],
            ];

            $subscribers = \array_merge(...\array_values($subscribers));

            // Foundry can only handle data provider since PHPUnit 11.4
            if (!ConstraintRequirement::from('>=11.4')->isSatisfiedBy(Runner\Version::id())) {
                $subscribers = \array_filter(
                    $subscribers,
                    static fn($subscriber) => !$subscriber instanceof DataProviderSubscriberInterface
                );
            }

            $facade->registerSubscribers(...$subscribers);

            self::$enabled = true;
        }

        public static function shouldBeEnabled(): bool
        {
            return \defined('PHPUNIT_COMPOSER_INSTALL') && !self::isEnabled() && ConstraintRequirement::from('>=10')->isSatisfiedBy(Runner\Version::id());
        }

        public static function isEnabled(): bool
        {
            return self::$enabled;
        }
    }
} else {
    final class FoundryExtension
    {
        public static function shouldBeEnabled(): bool
        {
            return false;
        }

        public static function isEnabled(): bool
        {
            return false;
        }
    }
}
