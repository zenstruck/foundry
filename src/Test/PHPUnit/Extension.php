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

namespace Zenstruck\Foundry\Test\PHPUnit;

use PHPUnit\Metadata\Version\ConstraintRequirement;
use PHPUnit\Runner;
use PHPUnit\TextUI;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class Extension implements Runner\Extension\Extension
{
    public const MIN_PHPUNIT_VERSION = '11.4';

    public function bootstrap(
        TextUI\Configuration\Configuration $configuration,
        Runner\Extension\Facade $facade,
        Runner\Extension\ParameterCollection $parameters,
    ): void {
        if (!ConstraintRequirement::from(self::MIN_PHPUNIT_VERSION)->isSatisfiedBy(Runner\Version::id())) {
            throw new \LogicException(\sprintf('Your PHPUnit version (%s) is not compatible with the minimum version (%s) needed to use this extension.', Runner\Version::id(), self::MIN_PHPUNIT_VERSION));
        }

        // shutdown Foundry if for some reason it has been booted before
        if (Configuration::isBooted()) {
            Configuration::shutdown();
        }

        // todo: should we deal with different options passed to createKernel/bootKernel?
        $kernel = $this->createKernel();

        $facade->registerSubscribers(
            // DataProviderMethodCalled
            new BootFoundryOnDataProviderMethodCalled($kernel),
            new EnableInMemoryOnDataProviderMethodCalled(),

            // DataProviderMethodCalledFinished
            new DisableInMemoryOnDataProviderMethodFinished(),

            // TestSuiteLoaded
            new ShutdownKernelOnTestSuiteLoaded($kernel),
        );
    }

    /**
     * This logic was shamelessly stolen from Symfony's KernelTestCase.
     */
    private function createKernel(): KernelInterface
    {
        if (!isset($_SERVER['KERNEL_CLASS']) && !isset($_ENV['KERNEL_CLASS'])) {
            throw new \LogicException('You must set the KERNEL_CLASS environment variable to the fully-qualified class name of your Kernel in phpunit.xml / phpunit.xml.dist.');
        }

        if (!\class_exists($class = $_ENV['KERNEL_CLASS'] ?? $_SERVER['KERNEL_CLASS'])) {
            throw new \RuntimeException(\sprintf('Class "%s" doesn\'t exist or cannot be autoloaded. Check that the KERNEL_CLASS value in phpunit.xml matches the fully-qualified class name of your Kernel.', $class));
        }

        /**
         * @var class-string<KernelInterface> $class
         */
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new $class($env, $debug);
    }
}
