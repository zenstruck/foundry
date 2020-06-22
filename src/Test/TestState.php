<?php

namespace Zenstruck\Foundry\Test;

use Faker;
use Psr\Container\ContainerInterface;
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
    private static ?Faker\Generator $faker = null;
    private static bool $useBundle = true;
    private static array $globalStates = [];

    public static function setInstantiator(callable $instantiator): void
    {
        self::$instantiator = $instantiator;
    }

    public static function setFaker(Faker\Generator $faker): void
    {
        self::$faker = $faker;
    }

    public static function withoutBundle(): void
    {
        self::$useBundle = false;
    }

    public static function addGlobalState(callable $callback): void
    {
        self::$globalStates[] = $callback;
    }

    public static function bootFactory(Configuration $configuration): Configuration
    {
        if (self::$instantiator) {
            $configuration->setInstantiator(self::$instantiator);
        }

        if (self::$faker) {
            $configuration->setFaker(self::$faker);
        }

        Factory::boot($configuration);

        return $configuration;
    }

    /**
     * @internal
     */
    public static function bootFromContainer(ContainerInterface $container): Configuration
    {
        if (self::$useBundle) {
            return self::bootFactory($container->get(Configuration::class));
        }

        return self::bootFactory(new Configuration($container->get('doctrine'), new StoryManager([])));
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
