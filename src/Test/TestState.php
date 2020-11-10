<?php

namespace Zenstruck\Foundry\Test;

use Faker;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\StoryManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestState
{
    /** @var callable|null */
    private static $instantiator;

    /** @var Faker\Generator|null */
    private static $faker;

    /** @var bool */
    private static $alwaysAutoRefreshProxies = false;

    /** @var callable[] */
    private static $globalStates = [];

    public static function setInstantiator(callable $instantiator): void
    {
        self::$instantiator = $instantiator;
    }

    public static function setFaker(Faker\Generator $faker): void
    {
        self::$faker = $faker;
    }

    public static function alwaysAutoRefreshProxies(): void
    {
        self::$alwaysAutoRefreshProxies = true;
    }

    /**
     * @deprecated Foundry now auto-detects if the bundle is installed
     */
    public static function withoutBundle(): void
    {
        trigger_deprecation('zenstruck\foundry', '1.4.0', 'TestState::withoutBundle() is deprecated, the bundle is now auto-detected.');
    }

    public static function addGlobalState(callable $callback): void
    {
        self::$globalStates[] = $callback;
    }

    public static function bootFoundry(?Configuration $configuration = null): void
    {
        $configuration = $configuration ?? new Configuration();

        if (self::$instantiator) {
            $configuration->setInstantiator(self::$instantiator);
        }

        if (self::$faker) {
            $configuration->setFaker(self::$faker);
        }

        if (self::$alwaysAutoRefreshProxies) {
            $configuration->alwaysAutoRefreshProxies();
        }

        Factory::boot($configuration);
    }

    public static function shutdownFoundry(): void
    {
        Factory::shutdown();
        StoryManager::reset();
    }

    /**
     * @deprecated use TestState::bootFoundry()
     */
    public static function bootFactory(Configuration $configuration): Configuration
    {
        trigger_deprecation('zenstruck\foundry', '1.4.0', 'TestState::bootFactory() is deprecated, use TestState::bootFoundry().');

        self::bootFoundry($configuration);

        return Factory::configuration();
    }

    /**
     * @internal
     */
    public static function bootFromContainer(ContainerInterface $container): void
    {
        if ($container->has(Configuration::class)) {
            self::bootFoundry($container->get(Configuration::class));

            return;
        }

        $configuration = new Configuration();

        try {
            $configuration->setManagerRegistry($container->get('doctrine'));
        } catch (NotFoundExceptionInterface $e) {
            throw new \LogicException('Could not boot Foundry, is the DoctrineBundle installed/configured?', 0, $e);
        }

        self::bootFoundry($configuration);
    }

    /**
     * @internal
     */
    public static function flushGlobalState(): void
    {
        StoryManager::globalReset();

        foreach (self::$globalStates as $callback) {
            $callback();
        }

        StoryManager::setGlobalState();
    }
}
