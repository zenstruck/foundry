<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Tests\Benchmark;

use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeMethods;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Service\ResetInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Persistence\ResetDatabase\ResetDatabaseManager;

#[BeforeMethods(['_bootFoundry'])]
#[AfterMethods(['_shutdownFoundry'])]
abstract class KernelBench
{
    /** @var class-string<KernelInterface>|null */
    protected static ?string $class = null;
    protected static ?KernelInterface $kernel = null;
    protected static bool $booted = false;

    /**
     * @internal
     */
    public function _bootFoundry(): void
    {
        static::bootKernel();

        Configuration::boot(static function(): Configuration {
            if (!static::getContainer()->has('.zenstruck_foundry.configuration')) {
                throw new \LogicException('ZenstruckFoundryBundle is not enabled. Ensure it is added to your config/bundles.php.');
            }

            return static::getContainer()->get('.zenstruck_foundry.configuration'); // @phpstan-ignore return.type
        });
    }

    /**
     * @internal
     */
    public function _shutdownFoundry(): void
    {
        Configuration::shutdown();

        static::ensureKernelShutdown();
    }

    /**
     * @internal
     */
    public static function _resetDatabaseBeforeFirstBench(): void
    {
        $kernel = static::bootKernel();

        ResetDatabaseManager::resetBeforeFirstTest($kernel);

        static::ensureKernelShutdown();
    }

    /**
     * @internal
     */
    public function _resetDatabaseBeforeEachBench(): void
    {
        $kernel = static::bootKernel();

        ResetDatabaseManager::resetBeforeEachTest($kernel);

        static::ensureKernelShutdown();
    }

    /**
     * @return class-string<KernelInterface>
     * @throws \RuntimeException
     * @throws \LogicException
     */
    protected static function getKernelClass(): string
    {
        if (!isset($_SERVER['KERNEL_CLASS']) && !isset($_ENV['KERNEL_CLASS'])) {
            throw new \LogicException(\sprintf('You must set the KERNEL_CLASS environment variable to the fully-qualified class name of your Kernel in phpunit.xml / phpunit.xml.dist or override the "%1$s::createKernel()" or "%1$s::getKernelClass()" method.', static::class));
        }

        if (!\class_exists($class = $_ENV['KERNEL_CLASS'] ?? $_SERVER['KERNEL_CLASS'])) {
            throw new \RuntimeException(\sprintf('Class "%s" doesn\'t exist or cannot be autoloaded. Check that the KERNEL_CLASS value in phpunit.xml matches the fully-qualified class name of your Kernel or override the "%s::createKernel()" method.', $class, static::class));
        }

        return $class; // @phpstan-ignore return.type
    }

    /**
     * @param array{environment?: string, debug?: bool} $options
     *
     * @see KernelTestCase::bootKernel()
     */
    protected static function bootKernel(array $options = []): KernelInterface
    {
        static::ensureKernelShutdown();

        $kernel = static::createKernel($options);
        $kernel->boot();
        static::$kernel = $kernel;
        static::$booted = true;

        return static::$kernel;
    }

    /**
     * Provides a dedicated test container with access to both public and private
     * services. The container will not include private services that have been
     * inlined or removed. Private services will be removed when they are not
     * used by other services.
     *
     * Using this method is the best way to get a container from your test code.
     *
     * @see KernelTestCase::getContainer()
     */
    protected static function getContainer(): Container
    {
        if (!static::$booted) {
            static::bootKernel();
        }

        try {
            return self::$kernel->getContainer()->get('test.service_container'); // @phpstan-ignore method.nonObject, return.type
        } catch (ServiceNotFoundException $e) {
            throw new \LogicException('Could not find service "test.service_container". Try updating the "framework.test" config to "true".', 0, $e);
        }
    }

    /**
     * @see KernelTestCase::createKernel()
     *
     * @param array{environment?: string, debug?: bool} $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        static::$class ??= static::getKernelClass();

        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new static::$class($env, false);
    }

    /**
     * Shuts the kernel down if it was used in the test - called by the tearDown method by default.
     *
     * @see KernelTestCase::ensureKernelShutdown()
     */
    protected static function ensureKernelShutdown(): void
    {
        if (null !== static::$kernel) {
            static::$kernel->boot();
            $container = static::$kernel->getContainer();

            if ($container->has('services_resetter')) {
                // Instantiate the service because Container::reset() only resets services that have been used
                $container->get('services_resetter');
            }

            static::$kernel->shutdown();
            static::$booted = false;

            if ($container instanceof ResetInterface) {
                $container->reset();
            }
        }
    }
}
