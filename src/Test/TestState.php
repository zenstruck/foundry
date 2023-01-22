<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test;

use Doctrine\Persistence\ManagerRegistry;
use Faker;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zenstruck\Foundry\ChainManagerRegistry;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\Instantiator;
use Zenstruck\Foundry\StoryManager;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestState
{
    /** @var callable|null */
    private static $instantiator;

    private static ?\Faker\Generator $faker = null;

    private static ?bool $defaultProxyAutoRefresh = null;

    /** @var callable[] */
    private static array $globalStates = [];

    /**
     * @deprecated Use TestState::configure()
     */
    public static function setInstantiator(callable $instantiator): void
    {
        trigger_deprecation('zenstruck\foundry', '1.23', 'Usage of TestState::setInstantiator() is deprecated. Please use TestState::configure().');

        self::$instantiator = $instantiator;
    }

    /**
     * @deprecated Use TestState::configure()
     */
    public static function setFaker(Faker\Generator $faker): void
    {
        trigger_deprecation('zenstruck\foundry', '1.23', 'Usage of TestState::setFaker() is deprecated. Please use TestState::configure().');

        self::$faker = $faker;
    }

    /**
     * @deprecated Use bundle configuration
     */
    public static function enableDefaultProxyAutoRefresh(): void
    {
        trigger_deprecation('zenstruck\foundry', '1.23', 'Usage of TestState::enableDefaultProxyAutoRefresh() is deprecated. Please use bundle configuration under "auto_refresh_proxies" key.');

        self::$defaultProxyAutoRefresh = true;
    }

    /**
     * @deprecated Use bundle configuration
     */
    public static function disableDefaultProxyAutoRefresh(): void
    {
        trigger_deprecation('zenstruck\foundry', '1.23', 'Usage of TestState::disableDefaultProxyAutoRefresh() is deprecated. Please use bundle configuration under "auto_refresh_proxies" key.');

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

    /**
     * @deprecated Use bundle configuration under "global_state" key
     */
    public static function addGlobalState(callable $callback): void
    {
        trigger_deprecation('zenstruck\foundry', '1.23', 'Usage of TestState::addGlobalState() is deprecated. Please use bundle configuration under "global_state" key.');

        self::$globalStates[] = $callback;
    }

    /**
     * @internal
     */
    public static function bootFoundryForUnitTest(): void
    {
        $configuration = new Configuration([], [], ORMDatabaseResetter::RESET_MODE_SCHEMA, []);

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

        self::bootFoundry($configuration);
    }

    /**
     * @internal
     */
    public static function bootFoundry(Configuration $configuration): void
    {
        Factory::boot($configuration);
    }

    /**
     * @internal
     */
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
        trigger_deprecation('zenstruck/foundry', '1.4.0', 'TestState::bootFactory() is deprecated, use TestState::bootFoundry().');

        self::bootFoundry($configuration);

        return Factory::configuration();
    }

    /**
     * @internal
     */
    public static function bootFromContainer(ContainerInterface $container): void
    {
        if ($container->has('.zenstruck_foundry.configuration')) {
            self::bootFoundry($container->get('.zenstruck_foundry.configuration'));

            return;
        }

        trigger_deprecation('zenstruck\foundry', '1.23', 'Usage of foundry without the bundle is deprecated and will not be possible anymore in 2.0.');

        $configuration = new Configuration([], [], ORMDatabaseResetter::RESET_MODE_SCHEMA, []);

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

        return new ChainManagerRegistry($managerRegistries);
    }

    /**
     * @internal
     */
    public static function flushGlobalState(?GlobalStateRegistry $globalStateRegistry): void
    {
        StoryManager::globalReset();

        foreach (self::$globalStates as $callback) {
            $callback();
        }

        if ($globalStateRegistry) {
            foreach ($globalStateRegistry->getGlobalStates() as $callback) {
                $callback();
            }
        }

        StoryManager::setGlobalState();
    }

    public static function configure(?Instantiator $instantiator = null, ?Faker\Generator $faker = null): void
    {
        self::$instantiator = $instantiator;
        self::$faker = $faker;
    }
}
