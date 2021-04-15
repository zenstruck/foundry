<?php

namespace Zenstruck\Foundry\Test;

use Doctrine\Persistence\ManagerRegistry;
use Faker;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zenstruck\Foundry\ChainManagerRegistry;
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

    /** @var bool|null */
    private static $defaultProxyAutoRefresh;

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

    public static function enableDefaultProxyAutoRefresh(): void
    {
        self::$defaultProxyAutoRefresh = true;
    }

    public static function disableDefaultProxyAutoRefresh(): void
    {
        self::$defaultProxyAutoRefresh = false;
    }

    /**
     * @deprecated Use TestState::enableDefaultProxyAutoRefresh()
     */
    public static function alwaysAutoRefreshProxies(): void
    {
        trigger_deprecation('zenstruck\foundry', '1.9', 'TestState::alwaysAutoRefreshProxies() is deprecated, use TestState::enableDefaultProxyAutoRefresh().');

        self::enableDefaultProxyAutoRefresh();
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

        if (true === self::$defaultProxyAutoRefresh) {
            $configuration->enableDefaultProxyAutoRefresh();
        } elseif (false === self::$defaultProxyAutoRefresh) {
            $configuration->disableDefaultProxyAutoRefresh();
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
            $configuration->setManagerRegistry(self::initializeChainManagerRegistry($container));
        } catch (NotFoundExceptionInterface $e) {
            throw new \LogicException('Could not boot Foundry, is the DoctrineBundle installed/configured?', 0, $e);
        }

        self::bootFoundry($configuration);
    }

    /**
     * @internal
     */
    public static function initializeChainManagerRegistry(ContainerInterface $container): ChainManagerRegistry
    {
        /** @var list<ManagerRegistry> $managerRegistries */
        $managerRegistries = [];

        if ($container->has('doctrine')) {
            $managerRegistries[] = $container->get('doctrine');
        }

        if ($container->has('doctrine_mongodb')) {
            $managerRegistries[] = $container->get('doctrine_mongodb');
        }

        if (0 === \count($managerRegistries)) {
            throw new \LogicException('Neither doctrine/orm nor doctrine/mongodb-odm are present.');
        }

        return new ChainManagerRegistry($managerRegistries);
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
